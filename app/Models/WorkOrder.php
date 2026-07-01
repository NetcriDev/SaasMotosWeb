<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use App\Support\TenancyPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class WorkOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_total' => 'decimal:2',
            'affected_systems' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WorkOrder $order): void {
            if ($order->isDirty('maintenance_type_id') && $order->maintenance_type_id && ! $order->isDirty('estimated_total')) {
                $maintenanceType = MaintenanceType::query()->find($order->maintenance_type_id);

                if ($maintenanceType !== null) {
                    $order->estimated_total = $maintenanceType->price;
                }
            }

            if ($order->motorcycle_id && $order->client_id) {
                $motorcycle = Motorcycle::query()->find($order->motorcycle_id);
                if ($motorcycle && (int) $motorcycle->client_id !== (int) $order->client_id) {
                    throw ValidationException::withMessages([
                        'motorcycle_id' => 'La moto no pertenece al cliente seleccionado.',
                    ]);
                }
            }

            if ($order->mechanic_id && $order->team_id) {
                $team = Team::query()->find($order->team_id);
                $mechanic = User::query()->find($order->mechanic_id);

                $isMechanic = $team && $mechanic && $mechanic->teams()
                    ->whereKey($team->getKey())
                    ->exists() && TenancyPermissions::withTeam(
                        $team,
                        fn (): bool => $mechanic->hasRole('mecanico'),
                    );

                if (! $isMechanic) {
                    throw ValidationException::withMessages([
                        'mechanic_id' => 'El empleado asignado debe ser mecanico de este taller.',
                    ]);
                }
            }
        });

        static::saved(function (WorkOrder $order): void {
            if ($order->wasChanged('maintenance_type_id')) {
                $order->recalculateEstimatedTotal();
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function maintenanceType(): BelongsTo
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkOrderActivity::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(WorkOrderProduct::class);
    }

    public function recalculateEstimatedTotal(): void
    {
        $base = (float) ($this->maintenanceType?->price ?? 0);
        $activitiesTotal = (float) $this->activities()
            ->where('is_billable', true)
            ->sum('service_cost');
        $productsTotal = (float) $this->products()
            ->where('is_billable', true)
            ->sum('line_total');

        $this->forceFill([
            'estimated_total' => $base + $activitiesTotal + $productsTotal,
        ])->saveQuietly();
    }
}
