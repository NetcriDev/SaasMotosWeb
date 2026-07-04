<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Support\TenantWorkOrderQuery;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'activas';
    }

    /**
     * @return array<string | int, Tab>
     */
    public function getTabs(): array
    {
        return [
            'activas' => Tab::make('Activas')
                ->icon(Heroicon::OutlinedBolt)
                ->badge(fn (): int => TenantWorkOrderQuery::make()
                    ->whereIn('status', WorkOrderStatus::activeValues())
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->whereIn('status', WorkOrderStatus::activeValues()),
                ),
            'recibido' => Tab::make('Recibido')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->badge(fn (): int => TenantWorkOrderQuery::make()
                    ->where('status', WorkOrderStatus::Received)
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkOrderStatus::Received),
                ),
            'en_taller' => Tab::make('En taller')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->badge(fn (): int => TenantWorkOrderQuery::make()
                    ->where('status', WorkOrderStatus::InProgress)
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkOrderStatus::InProgress),
                ),
            'listo' => Tab::make('Listo')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->badge(fn (): int => TenantWorkOrderQuery::make()
                    ->where('status', WorkOrderStatus::Ready)
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkOrderStatus::Ready),
                ),
            'entregado' => Tab::make('Entregado')
                ->icon(Heroicon::OutlinedTruck)
                ->badge(fn (): int => TenantWorkOrderQuery::make()
                    ->where('status', WorkOrderStatus::Delivered)
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', WorkOrderStatus::Delivered),
                ),
            'todas' => Tab::make('Todas')
                ->icon(Heroicon::OutlinedQueueList),
        ];
    }
}
