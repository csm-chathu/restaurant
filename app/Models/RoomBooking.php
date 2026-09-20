<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomBooking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'room_id', 'customer_id', 'user_id', 'booking_number',
        'guest_name', 'guest_phone', 'guest_nic', 'guest_count',
        'check_in_date', 'check_out_date', 'nights', 'check_in_time', 'check_out_time',
        'rate_per_night', 'room_total', 'charges_total', 'service_charge', 'service_charge_pct',
        'discount', 'total',
        'deposit', 'amount_paid', 'payment_method', 'payment_status',
        'status', 'notes',
    ];

    protected $casts = [
        'check_in_date' => 'string',
        'check_out_date' => 'string',
        'nights' => 'integer',
        'guest_count' => 'integer',
        'rate_per_night' => 'float',
        'room_total' => 'float',
        'charges_total' => 'float',
        'service_charge' => 'float',
        'service_charge_pct' => 'integer',
        'discount' => 'float',
        'total' => 'float',
        'deposit' => 'float',
        'amount_paid' => 'float',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(RoomBookingCharge::class, 'booking_id');
    }

    public function recalculateTotals(): void
    {
        $this->room_total    = $this->rate_per_night * $this->nights;
        $this->charges_total = $this->charges()->sum('amount');

        $serviceableTotal = $this->charges()
            ->whereIn('charge_type', ['food', 'beverage', 'room_service'])
            ->sum('amount');

        $pct = $this->service_charge_pct ?? 10;
        $this->service_charge = round($serviceableTotal * $pct / 100, 2);

        $this->total = $this->room_total + $this->charges_total + $this->service_charge - $this->discount;
        $this->save();
    }
}
