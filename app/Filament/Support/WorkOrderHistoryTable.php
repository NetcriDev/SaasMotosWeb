<?php

namespace App\Filament\Support;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Motorcycles\MotorcycleResource;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use App\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class WorkOrderHistoryTable
{
    public static function configure(
        Table $table,
        bool $showClient = true,
        bool $showMotorcycle = true,
    ): Table {
        $columns = [
            TextColumn::make('id')
                ->label('Nº')
                ->sortable(),
        ];

        if ($showClient) {
            $columns[] = TextColumn::make('client.name')
                ->label('Cliente')
                ->searchable()
                ->url(
                    fn (WorkOrder $record): ?string => $record->client_id
                        ? ClientResource::getUrl('edit', ['record' => $record->client_id])
                        : null,
                );
        }

        if ($showMotorcycle) {
            $columns[] = TextColumn::make('motorcycle.license_plate')
                ->label('Moto')
                ->searchable()
                ->url(
                    fn (WorkOrder $record): ?string => $record->motorcycle_id
                        ? MotorcycleResource::getUrl('edit', ['record' => $record->motorcycle_id])
                        : null,
                );
        }

        $columns = [
            ...$columns,
            TextColumn::make('branch.name')
                ->label('Sucursal')
                ->toggleable(),
            TextColumn::make('maintenanceType.name')
                ->label('Servicio')
                ->placeholder('—'),
            TextColumn::make('estimated_total')
                ->label('Total')
                ->formatStateUsing(fn ($state): string => Money::format($state))
                ->sortable(),
            TextColumn::make('status')
                ->label('Estado')
                ->formatStateUsing(fn (WorkOrderStatus $state): string => $state->label())
                ->badge()
                ->color(fn (WorkOrderStatus $state): string => match ($state) {
                    WorkOrderStatus::Received => 'gray',
                    WorkOrderStatus::InProgress => 'warning',
                    WorkOrderStatus::Ready => 'success',
                    WorkOrderStatus::Delivered => 'info',
                }),
            TextColumn::make('received_at')
                ->label('Ingreso')
                ->dateTime()
                ->sortable(),
            TextColumn::make('completed_at')
                ->label('Finalizado')
                ->dateTime()
                ->placeholder('—')
                ->sortable(),
        ];

        return $table
            ->columns($columns)
            ->defaultSort('received_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->url(fn (WorkOrder $record): string => WorkOrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
