<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Enums\WorkOrderStatus;
use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
                TextColumn::make('estimated_total')
                    ->label('Total est.')
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
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(self::filters(), layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters(false)
            ->filtersFormColumns(3)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->persistFiltersInSession()
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<Filter|SelectFilter>
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('branch_id')
                ->label('Sucursal')
                ->relationship(
                    name: 'branch',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->whereBelongsTo(Filament::getTenant(), 'team'),
                )
                ->searchable()
                ->preload()
                ->native(false),
            SelectFilter::make('status')
                ->label('Estado')
                ->options(WorkOrderStatus::options())
                ->native(false),
            Filter::make('received_at')
                ->label('Fecha de ingreso')
                ->schema([
                    DatePicker::make('from')
                        ->label('Desde'),
                    DatePicker::make('until')
                        ->label('Hasta'),
                ])
                ->columns(2)
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            filled($data['from'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate(
                                'received_at',
                                '>=',
                                $data['from'],
                            ),
                        )
                        ->when(
                            filled($data['until'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate(
                                'received_at',
                                '<=',
                                $data['until'],
                            ),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (filled($data['from'] ?? null)) {
                        $indicators[] = Indicator::make(
                            'Ingreso desde '.Carbon::parse($data['from'])->format('d/m/Y'),
                        )->removeField('from');
                    }

                    if (filled($data['until'] ?? null)) {
                        $indicators[] = Indicator::make(
                            'Ingreso hasta '.Carbon::parse($data['until'])->format('d/m/Y'),
                        )->removeField('until');
                    }

                    return $indicators;
                }),
        ];
    }
}
