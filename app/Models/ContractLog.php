<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class ContractLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * The table associated with the model.
     */
    protected $table = 'contract_logs';

    protected $fillable = [
        'contract_id',
        'performed_by',
        'description',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    /**
     * Get the contract that this log belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Accessor for formatted performed_at date.
     */
    public function getFormattedPerformedAtAttribute(): ?string
    {
        if ($this->performed_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->performed_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
