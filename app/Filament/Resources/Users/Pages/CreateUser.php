<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $role = $this->form->getRawState()['team_role'] ?? null;

        if (is_string($role) && $this->record instanceof User) {
            UserResource::syncTeamRole($this->record, $role);
        }
    }
}
