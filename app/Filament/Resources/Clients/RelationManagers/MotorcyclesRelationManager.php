<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\Motorcycles\MotorcycleResource;
use App\Models\Client;
use App\Models\Motorcycle;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MotorcyclesRelationManager extends RelationManager
{
    protected static string $relationship = 'motorcycles';

    protected static ?string $relatedResource = MotorcycleResource::class;

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedCube;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Motos';
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof Client) {
            return null;
        }

        return (string) $ownerRecord->motorcycles()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Motos del cliente')
            ->columns([
                TextColumn::make('license_plate')
                    ->label('Patente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Marca')
                    ->searchable(),
                TextColumn::make('motorcycleModel.name')
                    ->label('Modelo')
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->placeholder('—'),
            ])
            ->defaultSort('license_plate')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Client $client */
                        $client = $this->getOwnerRecord();

                        return [
                            ...$data,
                            'client_id' => $client->getKey(),
                            'branch_id' => $data['branch_id'] ?? $client->branch_id,
                        ];
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Motorcycle $record): string => MotorcycleResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
