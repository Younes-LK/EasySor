<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repair extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'title',
        'description',
        'cost',
        'assigned_to',
        'performed_date',
        'sms_sent',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'sms_sent' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(RepairEquipment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RepairPayment::class);
    }
}
