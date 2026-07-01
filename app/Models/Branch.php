<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $guarded = [];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function motorcycles(): HasMany
    {
        return $this->hasMany(Motorcycle::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }
}
