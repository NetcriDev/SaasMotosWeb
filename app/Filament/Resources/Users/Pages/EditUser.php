<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        $role = $this->form->getRawState()['team_role'] ?? null;

        if (is_string($role) && $this->record instanceof User) {
            UserResource::syncTeamRole($this->record, $role);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('detachFromTeam')
                ->label('Quitar del taller')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $team = Filament::getTenant();

                    if ($team && $this->record instanceof User) {
                        $this->record->teams()->detach($team->getKey());
                    }

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
