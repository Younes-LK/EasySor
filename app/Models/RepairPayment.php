<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian; // Ensure this is at the top
use Carbon\Carbon; // Ensure Carbon is imported

class RepairPayment extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'repair_payments'; // Explicitly define if not default

    protected $fillable = [
        'repair_id',
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
        'paid_at' => 'datetime', // <-- CRITICAL: Ensures paid_at is a Carbon instance
        'amount' => 'integer',   // Or 'float' / 'decimal:2'
    ];

    /**
     * Get the repair that this payment belongs to.
     */
    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    /**
     * Accessor for formatted paid_at.
     */
    public function getFormattedPaidAtAttribute(): ?string
    {
        if ($this->paid_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->paid_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
