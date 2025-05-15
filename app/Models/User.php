<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'password',
        'national_code',
        'phone',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function assignedContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'assigned_to');
    }

    public function assignedMaintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'assigned_to');
    }

    public function performedMaintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class, 'performed_by');
    }

    public function performedRepairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'assigned_to');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isStaff()
    {
        return $this->role === 'staff';
    }

}
