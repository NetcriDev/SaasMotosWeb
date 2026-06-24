<?php

namespace App\Filament\Resources\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Filament\Resources\WorkOrders\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\EditWorkOrder;
use App\Filament\Resources\WorkOrders\Pages\ListWorkOrders;
use App\Filament\Resources\WorkOrders\Tables\WorkOrdersTable;
use App\Models\Client;
use App\Models\MaintenanceType;
use App\Models\Motorcycle;
use App\Models\WorkOrder;
use App\Support\Money;
use BackedEnum;
use Filament\Facades\Filament;
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
                ->searchable(['name', 'email'])
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    $set('motorcycle_id', null);

                    if (blank($state)) {
                        return;
                    }

                    $motorcycleIds = Motorcycle::query()
                        ->where('client_id', $state)
                        ->limit(2)
                        ->pluck('id');

                    if ($motorcycleIds->count() === 1) {
                        $set('motorcycle_id', $motorcycleIds->first());
                    }
                })
                ->createOptionForm(self::clientQuickCreateForm())
                ->createOptionModalHeading('Nuevo cliente')
                ->createOptionUsing(function (array $data, Get $get): int {
                    $tenant = Filament::getTenant();

                    abort_unless($tenant !== null, 403);

                    $data['branch_id'] ??= $get('branch_id');

                    return $tenant->clients()->create($data)->getKey();
                }),
            Select::make('motorcycle_id')
                ->label('Moto')
                ->relationship(
                    name: 'motorcycle',
                    titleAttribute: 'license_plate',
                    modifyQueryUsing: fn ($query, Get $get) => $query->where('client_id', $get('client_id')),
                )
                ->getOptionLabelFromRecordUsing(fn (Motorcycle $record): string => self::motorcycleOptionLabel($record))
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (Get $get) => blank($get('client_id')))
                ->createOptionForm(self::motorcycleQuickCreateForm())
                ->createOptionModalHeading('Nueva moto del cliente')
                ->createOptionUsing(function (array $data, Get $get): int {
                    $tenant = Filament::getTenant();
                    $clientId = $get('client_id');

                    abort_unless($tenant !== null && filled($clientId), 403);

                    abort_unless(
                        Client::query()
                            ->whereKey($clientId)
                            ->where('team_id', $tenant->getKey())
                            ->exists(),
                        403,
                    );

                    $data['client_id'] = $clientId;
                    $data['branch_id'] ??= $get('branch_id');

                    return $tenant->motorcycles()->create($data)->getKey();
                }),
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

    private static function clientQuickCreateForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->required()
                ->maxLength(255),
            Select::make('branch_id')
                ->label('Sucursal principal')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload(),
        ];
    }

    private static function motorcycleQuickCreateForm(): array
    {
        return [
            TextInput::make('license_plate')
                ->label('Patente / placa')
                ->required()
                ->maxLength(32)
                ->helperText('Unica por taller. Se normaliza a mayusculas.')
                ->rule(static function (): \Closure {
                    return function (string $attribute, mixed $value, \Closure $fail): void {
                        $normalized = strtoupper(trim((string) $value));

                        if ($normalized === '') {
                            return;
                        }

                        $tenant = Filament::getTenant();

                        if ($tenant === null) {
                            return;
                        }

                        if (
                            Motorcycle::query()
                                ->where('team_id', $tenant->getKey())
                                ->where('license_plate', $normalized)
                                ->exists()
                        ) {
                            $fail('Ya existe una moto con esta patente en tu taller.');
                        }
                    };
                }),
            Select::make('branch_id')
                ->label('Sucursal habitual')
                ->relationship('branch', 'name')
                ->searchable()
                ->preload(),
            Select::make('brand_id')
                ->label('Marca')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('motorcycle_model_id', null)),
            Select::make('motorcycle_model_id')
                ->label('Modelo')
                ->relationship(
                    name: 'motorcycleModel',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn ($query, Get $get) => $query->where('brand_id', $get('brand_id')),
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabled(fn (Get $get) => blank($get('brand_id'))),
            TextInput::make('year')
                ->label('Anio')
                ->numeric()
                ->minValue(1900)
                ->maxValue((int) date('Y') + 1),
        ];
    }

    private static function motorcycleOptionLabel(Motorcycle $motorcycle): string
    {
        $details = collect([
            $motorcycle->brand?->name,
            $motorcycle->motorcycleModel?->name,
            $motorcycle->year,
        ])
            ->filter()
            ->implode(' ');

        return filled($details)
            ? "{$motorcycle->license_plate} - {$details}"
            : $motorcycle->license_plate;
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
