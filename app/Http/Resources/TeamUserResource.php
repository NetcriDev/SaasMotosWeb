<?php

namespace App\Http\Resources;

use App\Models\Team;
use App\Support\TenancyPermissions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $team = $request->route('team');
        $roles = [];

        if ($team instanceof Team) {
            $roles = TenancyPermissions::withTeam(
                $team,
                fn () => $this->roles()
                    ->where('roles.team_id', $team->id)
                    ->orderBy('roles.name')
                    ->pluck('roles.name')
                    ->values()
                    ->all(),
            );
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $roles,
        ];
    }
}
