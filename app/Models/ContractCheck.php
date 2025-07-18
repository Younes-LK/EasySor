<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class ContractCheck extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'contract_checks';

    protected $fillable = [
        'contract_id',
        'date',
        'bank_name',
        'serial_number',
        'sayadi_number',
        'in_name_of',
        'amount',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function getFormattedDateAttribute(): ?string
    {
        if ($this->date instanceof Carbon) {
            return Jalalian::fromCarbon($this->date)->format('Y/m/d');
        }
        return null;
    }
}
