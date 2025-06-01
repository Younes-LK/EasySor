<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Morilog\Jalali\Jalalian; // Ensure this is at the top
use Carbon\Carbon; // Ensure Carbon is imported

class Repair extends Model
{
    protected $fillable = [
        'customer_id',
        'customer_address_id',
        'title',
        'description',
        'cost',
        'assigned_to',    // This should match the foreign key column name for the user
        'performed_date',
        'sms_sent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'performed_date' => 'date', // Cast to Carbon date object (time part will be 00:00:00)
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sms_sent' => 'boolean',
        'cost' => 'integer', // Or 'float' / 'decimal:2' if applicable
    ];

    /**
     * Get the customer that owns the repair.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the customer address for the repair.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    /**
     * Get the user (technician) assigned to the repair.
     * Assumes 'assigned_to' is the foreign key in 'repairs' table referencing 'users.id'.
     * If your foreign key or relation name is different, adjust accordingly.
     * For example, if the method in RepairController uses $repairItem->user->name, this should be 'user'.
     */
    public function user(): BelongsTo // Or assignedUser() if you prefer
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the equipment used for the repair.
     */
    public function equipments(): HasMany
    {
        return $this->hasMany(RepairEquipment::class);
    }

    /**
     * Get the payments for the repair.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(RepairPayment::class);
    }

    /**
     * Accessor for formatted performed_date.
     */
    public function getFormattedPerformedDateAttribute(): ?string
    {
        if ($this->performed_date instanceof Carbon) {
            return Jalalian::fromCarbon($this->performed_date)->format('Y/m/d');
        }
        return null;
    }

    /**
     * Accessor for formatted created_at.
     */
    public function getFormattedCreatedAtAttribute(): ?string
    {
        if ($this->created_at instanceof Carbon) {
            return Jalalian::fromCarbon($this->created_at)->format('Y/m/d H:i');
        }
        return null;
    }
}
