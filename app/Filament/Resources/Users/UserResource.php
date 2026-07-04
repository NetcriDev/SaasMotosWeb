<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Support\ShieldBootstrap;
use App\Support\TenancyPermissions;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $tenantRelationshipName = 'members';

    protected static ?string $tenantOwnershipRelationshipName = 'teams';

    protected static ?string $modelLabel = 'empleado';

    protected static ?string $pluralModelLabel = 'empleados';

    protected static ?string $navigationLabel = 'Empleados';

    protected static string|UnitEnum|null $navigationGroup = 'Equipo';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            TextInput::make('password')
                ->label('Contrasena')
                ->password()
                ->revealable()
                ->rule(Password::defaults())
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->dehydrateStateUsing(fn (string $state): string => Hash::make($state)),
            Select::make('team_role')
                ->label('Rol en este taller')
                ->options(fn (): array => self::roleOptions())
                ->default('recepcion')
                ->required()
                ->dehydrated(false)
                ->afterStateHydrated(function (Select $component, ?User $record): void {
                    $team = Filament::getTenant();

                    if (! $record instanceof User || ! $team instanceof Team) {
                        return;
                    }

                    $component->state(
                        TenancyPermissions::withTeam(
                            $team,
                            fn () => $record->roles()
                                ->where('roles.team_id', $team->id)
                                ->value('name'),
                        ),
                    );
                }),
            Select::make('team_branch_id')
                ->label('Sucursal asignada')
                ->options(fn (): array => self::branchOptions())
                ->searchable()
                ->preload()
                ->required()
                ->dehydrated(false)
                ->afterStateHydrated(function (Select $component, ?User $record): void {
                    $team = Filament::getTenant();

                    if (! $record instanceof User || ! $team instanceof Team) {
                        return;
                    }

                    $component->state(self::branchIdForTeam($record, $team));
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('team_role')
                    ->label('Rol')
                    ->state(fn (User $record): ?string => self::currentRoleLabel($record))
                    ->badge(),
                TextColumn::make('team_branch')
                    ->label('Sucursal')
                    ->state(fn (User $record): ?string => self::currentBranchLabel($record))
                    ->placeholder('Sin asignar'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('is_system_admin', false)
            ->whereHas('teams', fn (Builder $query): Builder => $query->whereKey($team->id));
    }

    public static function syncTeamRole(User $user, string $roleName): void
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return;
        }

        if (! in_array($roleName, ShieldBootstrap::defaultTeamRoleNames(), true)) {
            return;
        }

        ShieldBootstrap::ensureDefaultTeamRoles($team);

        Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $team->id,
        ]);

        TenancyPermissions::assignRole($user, $roleName, $team);
    }

    public static function syncTeamBranch(User $user, int|string|null $branchId): void
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return;
        }

        $branchId = filled($branchId) ? (int) $branchId : null;

        if ($branchId !== null) {
            $exists = Branch::query()
                ->whereKey($branchId)
                ->where('team_id', $team->id)
                ->exists();

            if (! $exists) {
                return;
            }
        }

        $user->teams()->syncWithoutDetaching([
            $team->id => ['branch_id' => $branchId],
        ]);
    }

    public static function roleOptions(): array
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return [];
        }

        ShieldBootstrap::ensureDefaultTeamRoles($team);

        return Role::query()
            ->where('team_id', $team->id)
            ->whereIn('name', ShieldBootstrap::defaultTeamRoleNames())
            ->orderBy('name')
            ->pluck('name', 'name')
            ->map(fn (string $role): string => self::roleLabel($role))
            ->all();
    }

    public static function branchOptions(): array
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return [];
        }

        return $team->branches()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function currentRoleLabel(User $user): ?string
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return null;
        }

        $role = TenancyPermissions::withTeam(
            $team,
            fn () => $user->roles()
                ->where('roles.team_id', $team->id)
                ->value('name'),
        );

        return $role ? self::roleLabel($role) : null;
    }

    private static function currentBranchLabel(User $user): ?string
    {
        $team = Filament::getTenant();

        if (! $team instanceof Team) {
            return null;
        }

        $branchId = self::branchIdForTeam($user, $team);

        if ($branchId === null) {
            return null;
        }

        return Branch::query()
            ->whereKey($branchId)
            ->where('team_id', $team->id)
            ->value('name');
    }

    private static function branchIdForTeam(User $user, Team $team): ?int
    {
        $pivot = $user->teams()
            ->whereKey($team->id)
            ->first()
            ?->pivot;

        return filled($pivot?->branch_id) ? (int) $pivot->branch_id : null;
    }

    private static function roleLabel(string $role): string
    {
        return match ($role) {
            'supervisor' => 'Supervisor',
            'recepcion' => 'Recepcionista',
            'mecanico' => 'Mecanico',
            default => $role,
        };
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
