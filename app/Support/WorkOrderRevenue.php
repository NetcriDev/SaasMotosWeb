<?php

namespace App\Support;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class WorkOrderRevenue
{
    public static function monthlyDeliveredTotal(Builder $query, ?Carbon $month = null): float
    {
        $month ??= Carbon::now();

        return (float) (clone $query)
            ->where('status', WorkOrderStatus::Delivered)
            ->whereBetween('completed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->sum('estimated_total');
    }

    public static function activePipelineTotal(Builder $query): float
    {
        return (float) (clone $query)
            ->whereIn('status', WorkOrderStatus::activeValues())
            ->sum('estimated_total');
    }
}
