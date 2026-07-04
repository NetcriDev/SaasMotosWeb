<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Support\ShieldBootstrap;
use Illuminate\Database\Seeder;

class DefaultTeamRolesSeeder extends Seeder
{
    public function run(): void
    {
        Team::query()
            ->orderBy('id')
            ->each(fn (Team $team): mixed => ShieldBootstrap::ensureDefaultTeamRoles($team));
    }
}
