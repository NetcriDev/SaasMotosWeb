<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Support\DefaultWorkshopCatalog;
use Illuminate\Database\Seeder;

class DefaultWorkshopCatalogSeeder extends Seeder
{
    public function run(): void
    {
        Team::query()
            ->orderBy('id')
            ->each(fn (Team $team): mixed => DefaultWorkshopCatalog::ensureForTeam($team));
    }
}
