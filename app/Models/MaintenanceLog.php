<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class MaintenanceLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'maintenances_logs'; // Matches migration 2025_05_09_012915_create_maintenances_logs_table.php

    protected $fillable = [
        'maintenance_service_id',
        'performed_by', // User ID of the technician
        'performed_at',
        'sms_sent',
        'note',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'sms_sent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the maintenance service this log belongs to.
     */
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_service_id');
    }

    /**
     * Get the user (technician) who performed this maintenance log.
     */
    public function user(): BelongsTo // Or performedByTechnician()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get all equipment used in this specific maintenance log entry.
     * Matches table 'maintenances_equipments' which has 'maintenance_log_id'.
     */
    public function equipments(): HasMany
    {
        return $this->hasMany(MaintenanceEquipment::class, 'maintenance_log_id');
    }

    // Accessor for formatted performed_at date
    public function getFormattedPerformedAtAttribute(): ?string
    {
        if ($this->performed_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->performed_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
