<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Genera/actualiza permisos Spatie de todos los recursos Filament (incl. módulos nuevos).
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--relationships' => true,
            '--no-interaction' => true,
        ]);

        $this->call([
            DemoDataSeeder::class,
            DefaultTeamRolesSeeder::class,
            DefaultWorkshopCatalogSeeder::class,
        ]);
    }
}
