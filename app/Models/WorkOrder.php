<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WorkOrder $order): void {
            if ($order->motorcycle_id && $order->client_id) {
                $motorcycle = Motorcycle::query()->find($order->motorcycle_id);
                if ($motorcycle && (int) $motorcycle->client_id !== (int) $order->client_id) {
                    throw ValidationException::withMessages([
                        'motorcycle_id' => 'La moto no pertenece al cliente seleccionado.',
                    ]);
                }
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

    public function maintenanceType(): BelongsTo
    {
        return $this->belongsTo(MaintenanceType::class);
    }
}
