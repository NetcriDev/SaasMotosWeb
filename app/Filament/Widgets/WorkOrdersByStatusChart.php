<?php

namespace App\Filament\Widgets;

use App\Enums\WorkOrderStatus;
use App\Support\TenantWorkOrderQuery;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class WorkOrdersByStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Órdenes por estado';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    public static function canView(): bool
    {
        return Filament::getTenant() !== null;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $counts = TenantWorkOrderQuery::make()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $data = [];
        $colors = [];

        foreach (WorkOrderStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
            $colors[] = match ($status) {
                WorkOrderStatus::Received => 'rgb(156, 163, 175)',
                WorkOrderStatus::InProgress => 'rgb(245, 158, 11)',
                WorkOrderStatus::Ready => 'rgb(34, 197, 94)',
                WorkOrderStatus::Delivered => 'rgb(59, 130, 246)',
            };
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
