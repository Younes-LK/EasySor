<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'assigned_to',
        'start_date',
        'duration_in_months',
        'monthly_price',
        'total_price',
        'paid_amount',
        'completed_count',
        'last_completed_at',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'last_completed_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'maintenance_service_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MaintenancePayment::class, 'maintenance_service_id');
    }

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
}
