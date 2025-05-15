<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairPayment extends Model
{
    protected $fillable = [
        'repair_id',
        'amount',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
