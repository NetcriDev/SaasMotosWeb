<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Api\Concerns\AuthorizesTeamAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderResource;
use App\Models\Team;
use App\Support\Money;
use App\Support\WorkOrderRevenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use AuthorizesTeamAccess;

    public function show(Request $request, Team $team): JsonResponse
    {
        $this->authorizeTeam($request, $team);

        $orders = $team->workOrders()->getQuery();
        $activeStatuses = WorkOrderStatus::activeValues();

        $monthlyRevenue = WorkOrderRevenue::monthlyDeliveredTotal(clone $orders);
        $activePipeline = WorkOrderRevenue::activePipelineTotal(clone $orders);

        $latestActiveOrders = $team->workOrders()
            ->with(['branch', 'client', 'motorcycle.brand', 'motorcycle.motorcycleModel', 'maintenanceType'])
            ->whereIn('status', $activeStatuses)
            ->latest('received_at')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'monthly_delivered_total' => $monthlyRevenue,
                'monthly_delivered_total_formatted' => Money::format($monthlyRevenue),
                'active_pipeline_total' => $activePipeline,
                'active_pipeline_total_formatted' => Money::format($activePipeline),
                'active_orders' => (clone $orders)->whereIn('status', $activeStatuses)->count(),
                'received_today' => (clone $orders)->whereDate('received_at', now()->toDateString())->count(),
                'received' => (clone $orders)->where('status', WorkOrderStatus::Received)->count(),
                'in_progress' => (clone $orders)->where('status', WorkOrderStatus::InProgress)->count(),
                'ready' => (clone $orders)->where('status', WorkOrderStatus::Ready)->count(),
                'delivered' => (clone $orders)->where('status', WorkOrderStatus::Delivered)->count(),
            ],
            'status_counts' => collect(WorkOrderStatus::cases())
                ->mapWithKeys(fn (WorkOrderStatus $status): array => [
                    $status->value => (clone $orders)->where('status', $status)->count(),
                ]),
            'latest_active_orders' => WorkOrderResource::collection($latestActiveOrders),
        ]);
    }
}
