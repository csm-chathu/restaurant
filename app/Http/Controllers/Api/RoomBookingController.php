<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\RoomBookingCharge;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoomBookingController extends Controller
{
    public function guestLookup(Request $request)
    {
        $user  = $request->user();
        $query = trim($request->query('q', ''));

        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $bookings = RoomBooking::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->where(function ($q) use ($query) {
                $q->where('guest_phone', 'like', "%{$query}%")
                  ->orWhere('guest_nic',   'like', "%{$query}%")
                  ->orWhere('guest_name',  'like', "%{$query}%");
            })
            ->with('room:id,room_number,name,type,category')
            ->orderByDesc('check_in_date')
            ->limit(10)
            ->get(['id', 'booking_number', 'room_id', 'guest_name', 'guest_phone', 'guest_nic', 'guest_count', 'check_in_date', 'check_out_date', 'nights', 'status', 'total', 'payment_status']);

        return response()->json($bookings);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = RoomBooking::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->date, fn($q, $d) => $q->where('check_in_date', $d))
            ->with(['room:id,room_number,name,type,category', 'user:id,name', 'charges'])
            ->latest()
            ->paginate(30);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_name' => 'required|string|max:150',
            'guest_phone' => 'required|string|max:30',
            'guest_nic' => 'nullable|string|max:50',
            'guest_count' => 'integer|min:1',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'check_in_time' => 'nullable|date_format:H:i',
            'rate_per_night' => 'required|numeric|min:0',
            'deposit' => 'nullable|numeric|min:0',
            'payment_method' => 'in:cash,card,bank_transfer,other',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $room = Room::findOrFail($data['room_id']);

        if (!$user->isAdmin() && $room->branch_id !== $user->branch_id) {
            abort(403);
        }

        if ($room->status === 'occupied') {
            return response()->json(['message' => 'Room is currently occupied.'], 422);
        }

        $checkIn = Carbon::parse($data['check_in_date']);
        $checkOut = Carbon::parse($data['check_out_date']);
        $nights = max(1, $checkIn->diffInDays($checkOut));

        $booking = DB::transaction(function () use ($data, $user, $room, $nights) {
            $roomTotal = $data['rate_per_night'] * $nights;
            $deposit = $data['deposit'] ?? 0;

            $booking = RoomBooking::create([
                'branch_id' => $room->branch_id,
                'room_id' => $room->id,
                'user_id' => $user->id,
                'booking_number' => $this->generateBookingNumber($room->branch_id),
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'] ?? null,
                'guest_nic' => $data['guest_nic'] ?? null,
                'guest_count' => $data['guest_count'] ?? 1,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'nights' => $nights,
                'check_in_time' => $data['check_in_time'] ?? null,
                'rate_per_night' => $data['rate_per_night'],
                'room_total' => $roomTotal,
                'charges_total' => 0,
                'service_charge' => 0,
                'service_charge_pct' => 10,
                'discount' => 0,
                'total' => $roomTotal,
                'deposit' => $deposit,
                'amount_paid' => $deposit,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_status' => $deposit >= $roomTotal ? 'paid' : ($deposit > 0 ? 'partial' : 'pending'),
                'status' => 'reserved',
                'notes' => $data['notes'] ?? null,
            ]);

            $room->update(['status' => 'occupied']);

            return $booking;
        });

        $booking->load(['room:id,room_number,name,type,category']);

        return response()->json($booking, 201);
    }

    public function show(Request $request, RoomBooking $roomBooking)
    {
        $this->authorizeBranch($request, $roomBooking);

        $roomBooking->load(['room', 'user:id,name', 'charges.product:id,name,selling_price']);

        return response()->json($roomBooking);
    }

    public function checkIn(Request $request, RoomBooking $roomBooking)
    {
        $this->authorizeBranch($request, $roomBooking);

        if ($roomBooking->status !== 'reserved') {
            return response()->json(['message' => 'Booking is not in reserved status.'], 422);
        }

        $roomBooking->update([
            'status' => 'checked_in',
            'check_in_time' => now()->format('H:i'),
        ]);

        return response()->json($roomBooking);
    }

    public function addCharge(Request $request, RoomBooking $roomBooking)
    {
        $this->authorizeBranch($request, $roomBooking);

        if (!in_array($roomBooking->status, ['reserved', 'checked_in'])) {
            return response()->json(['message' => 'Cannot add charges to a closed booking.'], 422);
        }

        $data = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'description' => 'required|string|max:255',
            'charge_type' => 'in:food,beverage,laundry,room_service,other',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $qty = $data['quantity'] ?? 1;
        $amount = $data['unit_price'] * $qty;

        $charge = DB::transaction(function () use ($roomBooking, $data, $qty, $amount, $request) {
            $charge = RoomBookingCharge::create([
                'booking_id' => $roomBooking->id,
                'product_id' => $data['product_id'] ?? null,
                'created_by' => $request->user()->id,
                'description' => $data['description'],
                'charge_type' => $data['charge_type'] ?? 'food',
                'unit_price' => $data['unit_price'],
                'quantity' => $qty,
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
            ]);

            $roomBooking->recalculateTotals();

            return $charge;
        });

        return response()->json($charge->load('product:id,name,selling_price'), 201);
    }

    public function removeCharge(Request $request, RoomBooking $roomBooking, RoomBookingCharge $charge)
    {
        $this->authorizeBranch($request, $roomBooking);

        if ($charge->booking_id !== $roomBooking->id) {
            abort(404);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        DB::transaction(function () use ($roomBooking, $charge) {
            $charge->delete();
            $roomBooking->recalculateTotals();
        });

        return response()->json(['message' => 'Charge removed.']);
    }

    public function checkout(Request $request, RoomBooking $roomBooking)
    {
        $this->authorizeBranch($request, $roomBooking);

        if (!in_array($roomBooking->status, ['reserved', 'checked_in'])) {
            return response()->json(['message' => 'Booking is already closed.'], 422);
        }

        $data = $request->validate([
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'in:cash,card,bank_transfer,other',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($roomBooking, $data) {
            $roomBooking->discount = $data['discount'] ?? $roomBooking->discount;
            $roomBooking->recalculateTotals();

            $totalPaid = $roomBooking->deposit + $data['amount_paid'];

            $roomBooking->update([
                'amount_paid' => $totalPaid,
                'payment_method' => $data['payment_method'] ?? $roomBooking->payment_method,
                'payment_status' => $totalPaid >= $roomBooking->total ? 'paid' : 'partial',
                'status' => 'checked_out',
                'check_out_time' => now()->format('H:i'),
                'notes' => $data['notes'] ?? $roomBooking->notes,
            ]);

            $roomBooking->room->update(['status' => 'cleaning']);
        });

        $roomBooking->load(['room', 'charges.product:id,name']);

        return response()->json($roomBooking);
    }

    public function cancel(Request $request, RoomBooking $roomBooking)
    {
        $this->authorizeBranch($request, $roomBooking);

        if (!in_array($roomBooking->status, ['reserved', 'checked_in'])) {
            return response()->json(['message' => 'Only reserved or checked-in bookings can be cancelled.'], 422);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        DB::transaction(function () use ($roomBooking, $request) {
            $roomBooking->update([
                'status' => 'cancelled',
                'notes'  => $roomBooking->notes
                    ? $roomBooking->notes . "\n[Cancelled] " . $request->reason
                    : '[Cancelled] ' . $request->reason,
            ]);
            $roomBooking->room->update(['status' => 'available']);
        });

        return response()->json($roomBooking->fresh('room'));
    }

    public function update(Request $request, RoomBooking $roomBooking)
    {
        $this->authorizeBranch($request, $roomBooking);

        if ($roomBooking->status === 'checked_out') {
            return response()->json(['message' => 'Cannot edit a checked-out booking.'], 422);
        }

        $data = $request->validate([
            'guest_name' => 'sometimes|string|max:150',
            'guest_phone' => 'nullable|string|max:30',
            'guest_nic' => 'nullable|string|max:50',
            'guest_count' => 'integer|min:1',
            'check_out_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $roomBooking->update($data);

        if (isset($data['check_out_date']) || isset($data['discount'])) {
            if (isset($data['check_out_date'])) {
                $checkIn = Carbon::parse($roomBooking->check_in_date);
                $checkOut = Carbon::parse($data['check_out_date']);
                $roomBooking->nights = max(1, $checkIn->diffInDays($checkOut));
                $roomBooking->save();
            }
            $roomBooking->recalculateTotals();
        }

        return response()->json($roomBooking->fresh(['room', 'charges']));
    }

    private function authorizeBranch(Request $request, RoomBooking $booking): void
    {
        $user = $request->user();
        if (!$user->isAdmin() && $booking->branch_id !== $user->branch_id) {
            abort(403);
        }
    }

    private function generateBookingNumber(int $branchId): string
    {
        $prefix = 'RB' . now()->format('ymd');
        $last = RoomBooking::where('booking_number', 'like', $prefix . '%')
            ->orderByDesc('booking_number')
            ->value('booking_number');
        $seq = $last ? (intval(substr($last, -4)) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
