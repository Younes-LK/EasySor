<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractPayment extends Model
{
    protected $fillable = [
        'contract_id',
        'title',
        'amount',
        'paid_at',
        'note',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
