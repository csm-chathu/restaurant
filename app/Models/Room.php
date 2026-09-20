<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = [
        'branch_id', 'room_number', 'name', 'type', 'category',
        'floor', 'max_guests', 'rate_per_night', 'status',
        'amenities', 'notes', 'is_active',
    ];

    protected $casts = [
        'rate_per_night' => 'float',
        'max_guests' => 'integer',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(RoomBooking::class);
    }

    public function activeBooking()
    {
        return $this->hasOne(RoomBooking::class)->whereIn('status', ['reserved', 'checked_in']);
    }
}
