<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceEquipment extends Model
{
    protected $fillable = [
        'maintenance_log_id',
        'equipment_id',
        'quantity',
        'unit_price',
        'notes',
    ];
}
