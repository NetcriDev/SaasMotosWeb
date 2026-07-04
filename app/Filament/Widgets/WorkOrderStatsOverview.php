<?php

namespace App\Filament\Widgets;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Support\Money;
use App\Support\TenantWorkOrderQuery;
use App\Support\WorkOrderRevenue;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class WorkOrderStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Órdenes de trabajo';

    protected ?string $description = 'Resumen operativo del taller';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Filament::getTenant() !== null;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $baseQuery = TenantWorkOrderQuery::make();
        $today = Carbon::today();

        $activeCount = (clone $baseQuery)
            ->whereIn('status', WorkOrderStatus::activeValues())
            ->count();

        $receivedTodayCount = (clone $baseQuery)
            ->whereDate('received_at', $today)
            ->count();

        $readyCount = (clone $baseQuery)
            ->where('status', WorkOrderStatus::Ready)
            ->count();

        $inProgressCount = (clone $baseQuery)
            ->where('status', WorkOrderStatus::InProgress)
            ->count();

        $monthlyRevenue = WorkOrderRevenue::monthlyDeliveredTotal($baseQuery);
        $pipelineValue = WorkOrderRevenue::activePipelineTotal($baseQuery);

        return [
            Stat::make('Ingresos del mes', Money::format($monthlyRevenue))
                ->description('Órdenes entregadas en '.Carbon::now()->translatedFormat('F'))
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->icon(Heroicon::OutlinedCurrencyEuro),
            Stat::make('Valor en taller', Money::format($pipelineValue))
                ->description('Suma de totales estimados activos')
                ->descriptionIcon(Heroicon::OutlinedCalculator)
                ->color('info')
                ->icon(Heroicon::OutlinedChartBar),
            Stat::make('Activas en taller', $activeCount)
                ->description('Recibido, en taller o listo para entrega')
                ->descriptionIcon(Heroicon::OutlinedWrenchScrewdriver)
                ->color('warning')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(WorkOrderResource::getUrl('index', ['tab' => 'activas'])),
            Stat::make('Ingresadas hoy', $receivedTodayCount)
                ->description($today->translatedFormat('d M Y'))
                ->descriptionIcon(Heroicon::OutlinedCalendarDays)
                ->color('primary')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(WorkOrderResource::getUrl('index', ['tab' => 'todas'])),
            Stat::make('En taller', $inProgressCount)
                ->description('Trabajo en curso')
                ->color('warning')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->url(WorkOrderResource::getUrl('index', ['tab' => 'en_taller'])),
            Stat::make('Listas para entrega', $readyCount)
                ->description('Pendientes de retiro')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->url(WorkOrderResource::getUrl('index', ['tab' => 'listo'])),
        ];
    }
}
