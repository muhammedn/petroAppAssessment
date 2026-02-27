<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferEvent extends Model
{
    protected $fillable = [
        'event_id',
        'station_id',
        'amount',
        'status',
        'event_created_at',
    ];

    protected $casts = [
        'amount'     => 'float',
        'event_created_at' => 'datetime',
    ];


    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForStation($query, string $stationId)
    {
        return $query->where('station_id', $stationId);
    }
}
