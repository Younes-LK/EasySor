<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Added for completeness
use Morilog\Jalali\Jalalian;

class ContractPayment extends Model
{
    protected $fillable = [
        'contract_id',
        'title',
        'amount',
        'paid_at',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'integer',
    ];

    public function getFormattedPaidAtAttribute(): ?string
    {
        // $this->paid_at will be a Carbon instance if $casts is set correctly
        if ($this->paid_at instanceof \Carbon\Carbon) {
            return Jalalian::fromCarbon($this->paid_at)->format('Y/m/d H:i');
        }
        return null;
    }

    /**
     * Get the contract that this payment belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
