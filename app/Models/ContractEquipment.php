<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractEquipment extends Model
{
    protected $fillable = [
        'contract_id',
        'equipment_id',
        'quantity',
        'unit_price',
        'notes',
    ];
}
