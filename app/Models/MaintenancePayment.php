<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenancePayment extends Model
{
    protected $fillable = [
        'maintenance_service_id',
        'amount',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
