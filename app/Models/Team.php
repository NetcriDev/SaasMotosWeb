<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id')
            ->withPivot('branch_id')
            ->withTimestamps();
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function motorcycles()
    {
        return $this->hasMany(Motorcycle::class);
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function motorcycleModels()
    {
        return $this->hasMany(MotorcycleModel::class);
    }

    public function maintenanceTypes()
    {
        return $this->hasMany(MaintenanceType::class);
    }

    public function inventoryProducts()
    {
        return $this->hasMany(InventoryProduct::class);
    }

    public function motorcycleSystems()
    {
        return $this->hasMany(MotorcycleSystem::class);
    }

    /** @return HasMany<Role, self> */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }
}
