<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryProduct extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function workOrderProducts(): HasMany
    {
        return $this->hasMany(WorkOrderProduct::class);
    }
}
