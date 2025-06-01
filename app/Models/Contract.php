<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Morilog\Jalali\Jalalian;

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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sms_sent' => 'boolean',
        'created_at' => 'datetime', // <-- CRITICAL: Ensures created_at is a Carbon instance
        'updated_at' => 'datetime', // <-- CRITICAL: Ensures updated_at is a Carbon instance
        // Add other date fields here if any, e.g., 'start_date' => 'date',
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

    public function getFormattedCreatedAtAttribute(): ?string
    {
        // $this->created_at will be a Carbon instance if $casts is set correctly
        if ($this->created_at instanceof \Carbon\Carbon) {
            return Jalalian::fromCarbon($this->created_at)->format('Y/m/d H:i');
        }
        return null;
    }

    public function getFormattedUpdatedAtAttribute(): ?string
    {
        if ($this->updated_at instanceof \Carbon\Carbon) {
            return Jalalian::fromCarbon($this->updated_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
