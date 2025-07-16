<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipments';

    protected $fillable = [
        'name',
        'price',
        'purchase_price',
        'stock_quantity',
        'unit',
        'brand',
        'description',
    ];
}
