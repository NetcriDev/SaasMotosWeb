<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderActivity extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'service_cost' => 'decimal:2',
            'is_billable' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $recalculate = function (WorkOrderActivity $activity): void {
            $activity->workOrder?->recalculateEstimatedTotal();
        };

        static::saved($recalculate);
        static::deleted($recalculate);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
