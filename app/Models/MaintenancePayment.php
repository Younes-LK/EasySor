<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class MaintenancePayment extends Model
{
    /**
     * The table associated with the model.
     * Migration 2025_05_09_013043_create_maintenances_payments_table.php uses 'maintenances_payments'.
     */
    protected $table = 'maintenances_payments';

    protected $fillable = [
        'maintenance_service_id', // This links directly to the 'maintenances' table's id
        'amount',
        'paid_at',
        'note',
        // 'title' column is NOT in the migration for maintenances_payments,
        // Unlike ContractPayment or RepairPayment. If needed, add migration.
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'integer', // Or 'float' / 'decimal:2'
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the maintenance service this payment belongs to.
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_service_id');
    }

    // Accessor for formatted paid_at date
    public function getFormattedPaidAtAttribute(): ?string
    {
        if ($this->paid_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->paid_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
