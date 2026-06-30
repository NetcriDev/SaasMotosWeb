<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use App\Support\ShieldBootstrap;
use Illuminate\Console\Command;

class AssignTeamOwnerCommand extends Command
{
    protected $signature = 'team:assign-owner
                            {email? : Correo del usuario}
                            {--team= : ID o nombre del taller}';

    protected $description = 'Asigna rol supervisor a un usuario en un taller';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email del usuario');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No existe el usuario [{$email}].");

            return self::FAILURE;
        }

        $teamQuery = Team::query();

        if ($teamOption = $this->option('team')) {
            $teamQuery->where(
                is_numeric($teamOption) ? 'id' : 'name',
                is_numeric($teamOption) ? (int) $teamOption : $teamOption,
            );
        }

        $team = $teamQuery->first();

        if ($team === null) {
            $this->error('No se encontró el taller.');

            return self::FAILURE;
        }

        $user->teams()->syncWithoutDetaching([$team->getKey()]);
        ShieldBootstrap::assignSupervisor($user, $team);

        $this->info("Usuario [{$email}] es supervisor de [{$team->name}].");

        return self::SUCCESS;
    }
}
