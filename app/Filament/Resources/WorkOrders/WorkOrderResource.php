<?php

namespace App\Filament\Resources\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrders\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Filament\Resources\WorkOrders\Tables\WorkOrdersTable;
use App\Models\MaintenanceType;
use App\Models\WorkOrder;
use App\Support\Money;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $tenantRelationshipName = 'workOrders';

    protected static ?string $modelLabel = 'orden de trabajo';

    protected static ?string $pluralModelLabel = 'órdenes de trabajo';

    protected static string|UnitEnum|null $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 30;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('branch_id')
                ->label('Sucursal')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('client_id')
                ->label('Cliente')
                ->relationship('client', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('motorcycle_id', null)),
            Select::make('motorcycle_id')
                ->label('Moto')
                ->relationship(
                    name: 'motorcycle',
                    titleAttribute: 'license_plate',
                    modifyQueryUsing: fn ($query, Get $get) => $query->where('client_id', $get('client_id')),
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (Get $get) => blank($get('client_id'))),
            Select::make('maintenance_type_id')
                ->label('Tipo de mantenimiento')
                ->relationship(
                    name: 'maintenanceType',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query) => $query->orderBy('name'),
                )
                ->getOptionLabelFromRecordUsing(
                    fn (MaintenanceType $record): string => sprintf(
                        '%s — %s',
                        $record->name,
                        Money::format($record->price),
                    ),
                )
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (blank($state)) {
                        return;
                    }

                    $maintenanceType = MaintenanceType::query()->find($state);

                    if ($maintenanceType !== null) {
                        $set('estimated_total', $maintenanceType->price);
                    }
                }),
            TextInput::make('estimated_total')
                ->label('Total estimado')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->prefix(Money::symbol())
                ->helperText(fn (Get $get): ?string => filled($get('maintenance_type_id'))
                    ? 'Puedes ajustar el total respecto al precio del catálogo.'
                    : 'Selecciona un tipo de servicio o ingresa un total manual.'),
            Select::make('status')
                ->label('Estado')
                ->options(WorkOrderStatus::options())
                ->default(WorkOrderStatus::Received->value)
                ->required(),
            DateTimePicker::make('received_at')
                ->label('Ingreso')
                ->default(now())
                ->required(),
            DateTimePicker::make('completed_at')
                ->label('Finalización'),
            Textarea::make('notes')
                ->label('Notas')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return WorkOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkOrders::route('/'),
            'create' => CreateWorkOrder::route('/create'),
            'edit' => EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
