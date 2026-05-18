<?php

namespace App\Filament\Pages;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamInvitationService;
use App\Support\TenancyPermissions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ManageTeamMembers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Equipo';

    protected static ?string $title = 'Equipo del taller';

    protected static string|UnitEnum|null $navigationGroup = 'Organización';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.manage-team-members';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && TenancyPermissions::userHasPermission($user, 'manage_team');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTeam()->members()->getQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->state(function (User $record): string {
                        $roles = TenancyPermissions::withTeam(
                            $this->getTeam(),
                            fn () => $record->getRoleNames(),
                        );

                        $role = $roles->first();

                        if ($role === null) {
                            return '—';
                        }

                        return TeamRole::tryFrom($role)?->label() ?? $role;
                    }),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Cambiar rol')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (User $record): bool => $this->canManageMember($record))
                    ->schema([
                        Select::make('role')
                            ->label('Rol')
                            ->options(TeamRole::invitableOptions())
                            ->required(),
                    ])
                    ->fillForm(function (User $record): array {
                        $role = TenancyPermissions::withTeam(
                            $this->getTeam(),
                            fn () => $record->roles()->first()?->name,
                        );

                        return ['role' => $role];
                    })
                    ->action(function (User $record, array $data): void {
                        TenancyPermissions::assignRole($record, $data['role'], $this->getTeam());

                        Notification::make()
                            ->title('Rol actualizado')
                            ->success()
                            ->send();
                    }),
                Action::make('remove')
                    ->label('Quitar del taller')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $this->canRemoveMember($record))
                    ->action(function (User $record): void {
                        $this->getTeam()->members()->detach($record);
                        TenancyPermissions::removeRoles($record, $this->getTeam());

                        Notification::make()
                            ->title('Miembro eliminado del taller')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invitar por correo')
                ->icon(Heroicon::OutlinedEnvelope)
                ->schema([
                    TextInput::make('email')
                        ->label('Correo')
                        ->email()
                        ->required(),
                    Select::make('role')
                        ->label('Rol')
                        ->options(TeamRole::invitableOptions())
                        ->default(TeamRole::Recepcion->value)
                        ->required(),
                ])
                ->action(function (array $data, TeamInvitationService $invitations): void {
                    $invitation = $invitations->invite(
                        $this->getTeam(),
                        $data['email'],
                        TeamRole::from($data['role']),
                        Auth::user(),
                    );

                    Notification::make()
                        ->title('Invitación creada')
                        ->body('Enlace: '.$invitation->acceptUrl())
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    public function getPendingInvitations()
    {
        return TeamInvitation::query()
            ->where('team_id', $this->getTeam()->getKey())
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();
    }

    protected function getTeam(): Team
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Team, 403);

        return $tenant;
    }

    protected function canManageMember(User $member): bool
    {
        if ($member->is(Auth::user())) {
            return false;
        }

        return ! $this->memberIsOwner($member);
    }

    protected function canRemoveMember(User $member): bool
    {
        if ($member->is(Auth::user())) {
            return false;
        }

        if ($this->memberIsOwner($member)) {
            return false;
        }

        return $this->countOwners() > 1 || ! $this->memberIsOwner($member);
    }

    protected function memberIsOwner(User $member): bool
    {
        return TenancyPermissions::withTeam(
            $this->getTeam(),
            fn (): bool => $member->hasRole(TeamRole::Owner->value),
        );
    }

    protected function countOwners(): int
    {
        return TenancyPermissions::withTeam($this->getTeam(), function (): int {
            return User::query()
                ->whereHas('teams', fn (Builder $query) => $query->whereKey($this->getTeam()->getKey()))
                ->whereHas('roles', fn (Builder $query) => $query->where('name', TeamRole::Owner->value))
                ->count();
        });
    }
}
