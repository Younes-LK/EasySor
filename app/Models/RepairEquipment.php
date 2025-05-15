<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairEquipment extends Model
{
    protected $fillable = [
        'repair_id',
        'equipment_id',
        'quantity',
        'unit_price',
        'notes',
    ];
}
