<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'stop_count',
        'total_price',
        'description',
        'assigned_to',
        'status',
        'sms_sent',
    ];

    protected $casts = [
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

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(ContractEquipment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ContractPayment::class);
    }
}
