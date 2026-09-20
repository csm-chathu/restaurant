<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBookingCharge extends Model
{
    protected $fillable = [
        'booking_id', 'product_id', 'created_by',
        'description', 'charge_type', 'unit_price', 'quantity', 'amount', 'notes',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'quantity' => 'float',
        'amount' => 'float',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(RoomBooking::class, 'booking_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
