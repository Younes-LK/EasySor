<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'assigned_to',
        'total_price',
        'notes',
        'status',
        'invoice_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total_price' => 'integer',
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
        return $this->hasMany(InvoiceEquipment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getFormattedInvoiceDateAttribute(): ?string
    {
        if ($this->invoice_date instanceof Carbon) {
            return Jalalian::fromCarbon($this->invoice_date)->format('Y/m/d');
        }
        return null;
    }

    public function getFormattedCreatedAtAttribute(): ?string
    {
        if ($this->created_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->created_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
