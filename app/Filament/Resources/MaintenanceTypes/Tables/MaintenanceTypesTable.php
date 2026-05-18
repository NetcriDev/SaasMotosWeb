<?php

namespace App\Filament\Resources\MaintenanceTypes\Tables;

use App\Support\Money;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state): string => Money::format($state))
                    ->sortable(),
                TextColumn::make('estimated_duration_minutes')
                    ->label('Duración')
                    ->formatStateUsing(fn (?int $state): string => $state ? "{$state} min" : '—')
                    ->sortable()
                    ->toggleable(),
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
