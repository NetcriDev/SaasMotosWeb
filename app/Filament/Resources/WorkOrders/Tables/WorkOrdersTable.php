<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Enums\WorkOrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Nº')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->searchable(),
                TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('motorcycle.license_plate')
                    ->label('Moto')
                    ->searchable(),
                TextColumn::make('maintenanceType.name')
                    ->label('Tipo de servicio')
                    ->placeholder('—')
                    ->toggleable(),
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
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
