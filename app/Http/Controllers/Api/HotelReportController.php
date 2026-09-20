<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomBooking;
use App\Models\RoomBookingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotelReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to',   now()->toDateString());

        // Fetch bookings in date range, branch-scoped
        $bookings = RoomBooking::with([
                'room:id,room_number,name,type,category',
                'charges',
            ])
            ->whereBetween('check_in_date', [$from, $to])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->get();

        // Only count revenue metrics from checked_out bookings
        $checkedOut = $bookings->where('status', 'checked_out');

        $summary = [
            'total_bookings'   => $bookings->count(),
            'checked_out'      => $checkedOut->count(),
            'cancelled'        => $bookings->where('status', 'cancelled')->count(),
            'active'           => $bookings->whereIn('status', ['active', 'checked_in'])->count(),
            'total_nights'     => (int) $checkedOut->sum('nights'),
            'room_revenue'     => round($checkedOut->sum('room_total'), 2),
            'charges_total'    => round($checkedOut->sum('charges_total'), 2),
            'service_charge'   => round($checkedOut->sum('service_charge'), 2),
            'discount'         => round($checkedOut->sum('discount'), 2),
            'grand_total'      => round($checkedOut->sum('total'), 2),
            'amount_collected' => round($checkedOut->sum('amount_paid'), 2),
        ];

        // Booking IDs in the period (all statuses) for charge breakdowns
        $bookingIds = $bookings->pluck('id');

        // Charges grouped by type
        $chargesByType = RoomBookingCharge::select(
                'charge_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->whereIn('booking_id', $bookingIds)
            ->groupBy('charge_type')
            ->get()
            ->map(fn($row) => [
                'charge_type' => $row->charge_type,
                'count'       => (int) $row->count,
                'total'       => round((float) $row->total, 2),
            ])
            ->values();

        // Top items by description
        $topItems = RoomBookingCharge::select(
                'description',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as orders')
            )
            ->whereIn('booking_id', $bookingIds)
            ->groupBy('description')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'description'  => $row->description,
                'total_qty'    => round((float) $row->total_qty, 2),
                'total_amount' => round((float) $row->total_amount, 2),
                'orders'       => (int) $row->orders,
            ])
            ->values();

        return response()->json([
            'from'            => $from,
            'to'              => $to,
            'summary'         => $summary,
            'bookings'        => $bookings->values(),
            'charges_by_type' => $chargesByType,
            'top_items'       => $topItems,
        ]);
    }
}
