<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceEquipment extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'maintenances_equipments'; // Matches migration 2025_05_09_013004_create_maintenances_equipments_table.php

    protected $fillable = [
        'maintenance_log_id',
        'equipment_id',
        'quantity',
        'unit_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer', // Or 'float' / 'decimal:2'
    ];

    /**
     * Get the maintenance log this equipment entry belongs to.
     */
    public function maintenanceLog(): BelongsTo
    {
        return $this->belongsTo(MaintenanceLog::class, 'maintenance_log_id');
    }

    /**
     * Get the equipment details for this entry.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
