<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairEquipment extends Model
{
    /**
     * The table associated with the model.
     * Laravel convention would be 'repair_equipments'.
     * Your migration 2025_05_09_013149_create_repair_equipments_table.php uses 'repair_equipments'.
     */
    protected $table = 'repair_equipments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'repair_id',
        'equipment_id',
        'quantity',
        'unit_price',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'integer', // Or 'float' / 'decimal:2' if you store cents/decimal prices
    ];

    /**
     * Get the repair that this equipment entry belongs to.
     */
    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }

    /**
     * Get the equipment details for this entry.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
