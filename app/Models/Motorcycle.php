<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Motorcycle extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Motorcycle $motorcycle): void {
            if (filled($motorcycle->license_plate)) {
                $motorcycle->license_plate = strtoupper(trim((string) $motorcycle->license_plate));
            }

            if ($motorcycle->motorcycle_model_id && $motorcycle->brand_id) {
                $model = MotorcycleModel::query()->find($motorcycle->motorcycle_model_id);
                if ($model && (int) $model->brand_id !== (int) $motorcycle->brand_id) {
                    throw ValidationException::withMessages([
                        'motorcycle_model_id' => 'El modelo no corresponde a la marca seleccionada.',
                    ]);
                }
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function motorcycleModel(): BelongsTo
    {
        return $this->belongsTo(MotorcycleModel::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
