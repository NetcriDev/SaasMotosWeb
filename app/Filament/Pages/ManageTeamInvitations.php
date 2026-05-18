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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ManageTeamInvitations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Invitaciones';

    protected static ?string $title = 'Invitar al equipo';

    protected static string|UnitEnum|null $navigationGroup = 'Equipo';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'invitaciones';

    protected string $view = 'filament.pages.manage-team-invitations';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        TenancyPermissions::setTeamFromFilament();

        return $user->can('ManageTeamInvitations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TeamInvitation::query()
                    ->where('team_id', $this->getTeam()->getKey())
                    ->whereNull('accepted_at')
                    ->where('expires_at', '>', now()),
            )
            ->columns([
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->formatStateUsing(fn (TeamRole $state): string => $state->label()),
                TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime(),
                TextColumn::make('accept_url')
                    ->label('Enlace')
                    ->state(fn (TeamInvitation $record): string => $record->acceptUrl())
                    ->copyable()
                    ->copyMessage('Enlace copiado'),
            ])
            ->paginated(false)
            ->emptyStateHeading('No hay invitaciones pendientes')
            ->emptyStateDescription('Invita a un compañero por correo. El rol se asigna al aceptar la invitación.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Nueva invitación')
                ->icon(Heroicon::OutlinedUserPlus)
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

    protected function getTeam(): Team
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Team, 403);

        return $tenant;
    }
}
