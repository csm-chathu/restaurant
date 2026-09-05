<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $purchases = Purchase::with(['supplier:id,name', 'user:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when(request('search'), fn($q, $s) => $q->where('purchase_number', 'like', "%$s%"))
            ->when(request('supplier_id'), fn($q, $s) => $q->where('supplier_id', $s))
            ->when(request('receivable'), fn($q) => $q->whereIn('status', ['draft', 'approved', 'sent', 'partial_received']))
            ->latest('purchased_at')
            ->paginate(request('per_page', 20));
        return response()->json($purchases);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'required|numeric|min:0',
            'tax'         => 'nullable|numeric|min:0',
            'status'      => 'required|in:draft,approved,sent,partial_received,completed,cancelled,received',
            'notes'       => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_cost'] * $i['quantity']);
            $tax      = $data['tax'] ?? 0;

            $purchase = Purchase::create([
                'branch_id'       => $request->user()->branch_id,
                'purchase_number' => (function () {
                    $prefix = 'PO-' . now()->format('Ymd') . '-';
                    $max = Purchase::withTrashed()->where('purchase_number', 'like', $prefix . '%')
                        ->max(DB::raw("CAST(SUBSTRING_INDEX(purchase_number, '-', -1) AS UNSIGNED)")) ?? 0;
                    return $prefix . str_pad((int) $max + 1, 4, '0', STR_PAD_LEFT);
                })(),
                'supplier_id'     => $data['supplier_id'],
                'user_id'         => $request->user()->id,
                'subtotal'        => $subtotal,
                'tax'             => $tax,
                'total'           => $subtotal + $tax,
                'status'          => $data['status'],
                'notes'           => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                if (!$request->user()->isAdmin() && $product->branch_id !== $request->user()->branch_id) {
                    throw new \Exception("Product is not available for your branch: {$product->name}");
                }

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total'       => $item['unit_cost'] * $item['quantity'],
                ]);
                if ($data['status'] === 'received') {
                    $product->increment('stock_quantity', $item['quantity']);
                    $product->refresh();
                    StockLedger::record(
                        $product,
                        'IN',
                        (float) $item['quantity'],
                        $request->user()->id,
                        $request->user()->branch_id,
                        'PO',
                        $purchase->id,
                        'Stock received directly from purchase',
                        ['purchase_number' => $purchase->purchase_number]
                    );
                }
            }

            DB::commit();
            return response()->json($purchase->load(['items.product', 'supplier', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        return response()->json($purchase->load(['items.product', 'supplier', 'user']));
    }

    public function updateStatus(Request $request, Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);
        $data = $request->validate([
            'status' => 'required|in:draft,approved,sent,partial_received,completed,cancelled,received',
        ]);

        $newStatus = $data['status'];

        // When marking as received directly (no GRN), increment stock for each item
        if ($newStatus === 'received' && $purchase->status !== 'received') {
            $hasGrns = Grn::where('purchase_id', $purchase->id)->exists();

            if (!$hasGrns) {
                DB::beginTransaction();
                try {
                    $purchase->load('items.product');
                    foreach ($purchase->items as $item) {
                        $product = $item->product;
                        if (!$product) continue;

                        $product->increment('stock_quantity', $item->quantity);
                        $product->refresh();

                        StockLedger::record(
                            $product,
                            'IN',
                            (float) $item->quantity,
                            $request->user()->id,
                            $purchase->branch_id,
                            'PURCHASE',
                            $purchase->id,
                            'Stock received — PO ' . $purchase->purchase_number,
                        );
                    }
                    $purchase->update(['status' => $newStatus]);
                    DB::commit();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return response()->json(['message' => $e->getMessage()], 422);
                }

                return response()->json($purchase->fresh());
            }
        }

        $purchase->update(['status' => $newStatus]);
        return response()->json($purchase);
    }

    public function destroy(Request $request, Purchase $purchase)
    {
        $this->authorizeBranch($purchase->branch_id);

        DB::beginTransaction();
        try {
            // Reverse stock for every GRN linked to this purchase
            $grns = Grn::where('purchase_id', $purchase->id)->with('items.product')->get();

            foreach ($grns as $grn) {
                foreach ($grn->items as $item) {
                    $product = $item->product;
                    $qty = (float) $item->quantity_received + (float) $item->free_quantity;

                    $product->decrement('stock_quantity', $qty);
                    $product->refresh();

                    StockLedger::record(
                        $product,
                        'OUT',
                        $qty,
                        $request->user()->id,
                        $purchase->branch_id,
                        'GRN_REVERSAL',
                        $grn->id,
                        'Stock reversed — PO ' . $purchase->purchase_number . ' / GRN ' . $grn->grn_number . ' deleted',
                    );
                }

                // Reverse accounting journal for each GRN
                JournalEntry::where('source_type', 'GRN')
                    ->where('source_id', $grn->id)
                    ->delete();
            }

            $purchase->delete(); // cascades to grns → grn_items via DB
            DB::commit();
            return response()->json(['message' => 'Purchase deleted and stock reversed.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
