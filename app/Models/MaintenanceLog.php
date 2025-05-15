<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceLog extends Model
{
    protected $fillable = [
        'maintenance_service_id',
        'performed_by',
        'performed_at',
        'sms_sent',
        'note',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'sms_sent' => 'boolean',
    ];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class, 'maintenance_service_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(MaintenanceEquipment::class, 'maintenance_log_id');
    }
}
