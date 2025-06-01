<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class Maintenance extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'maintenances'; // Matches migration 2025_05_09_012806_create_maintenances_table.php

    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'assigned_to',
        'start_date',
        'duration_in_months',
        'monthly_price',
        'total_price',
        'paid_amount',          // Typically calculated or updated via payments
        'completed_count',      // Typically updated when a log is added
        'last_completed_at',    // Typically updated when a log is added
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'last_completed_at' => 'datetime', // Can be 'date' if time isn't crucial
        'is_active' => 'boolean',
        'duration_in_months' => 'integer',
        'monthly_price' => 'integer',
        'total_price' => 'integer',
        'paid_amount' => 'integer',
        'completed_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    /**
     * The user (technician) assigned to this maintenance contract.
     */
    public function user(): BelongsTo // Or assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all logs for this maintenance service.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'maintenance_service_id'); // Matches foreign key in migration
    }

    /**
     * Get all payments for this maintenance service.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(MaintenancePayment::class, 'maintenance_service_id'); // Matches foreign key in migration
    }

    // Accessors for formatted dates
    public function getFormattedStartDateAttribute(): ?string
    {
        if ($this->start_date instanceof Carbon) {
            return Jalalian::fromCarbon($this->start_date)->format('Y/m/d');
        }
        return null;
    }

    public function getFormattedLastCompletedAtAttribute(): ?string
    {
        if ($this->last_completed_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->last_completed_at)->format('Y/m/d H:i');
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
