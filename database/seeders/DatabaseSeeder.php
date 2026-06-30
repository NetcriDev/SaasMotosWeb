<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (Permission::query()->doesntExist()) {
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--relationships' => true,
                '--no-interaction' => true,
            ]);
        }

        $this->call([
            DemoDataSeeder::class,
            DefaultTeamRolesSeeder::class,
            DefaultWorkshopCatalogSeeder::class,
        ]);
    }
}
