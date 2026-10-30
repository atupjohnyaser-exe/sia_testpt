<?php

namespace App\Console\Commands;

use App\Enums\Clearance;
use App\Models\User;
use Illuminate\Console\Command;

class CreateOfficer extends Command
{
    protected $signature = 'rpd:create-officer
        {badge : Badge ID, for example RP-1047}
        {name : Full name}
        {--rank=Officer : Rank shown in the app}
        {--clearance=2 : 1 clerk, 2 officer, 3 detective, 4 supervisor}
        {--password= : Leave empty to be asked, which keeps it out of shell history}';

    protected $description = 'Create an officer account, or reset the password of an existing one';

    public function handle(): int
    {
        $level = (int) $this->option('clearance');

        if (Clearance::tryFrom($level) === null) {
            $this->error('Clearance must be 1, 2, 3 or 4.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password (min 10 characters)');

        if (strlen((string) $password) < 10) {
            $this->error('The password must be at least 10 characters.');

            return self::FAILURE;
        }

        $badge = strtoupper(trim($this->argument('badge')));
        $name = $this->argument('name');
        $initials = collect(preg_split('/\s+/', preg_replace('/[^A-Za-z ]/', '', $name)))
            ->filter()
            ->map(fn ($part) => strtoupper($part[0]))
            ->take(2)
            ->implode('');

        $officer = User::updateOrCreate(
            ['badge_id' => $badge],
            [
                'name' => $name,
                'initials' => $initials ?: 'OF',
                'rank' => $this->option('rank'),
                'clearance_level' => $level,
                'is_active' => true,
                'password' => $password,
            ]
        );

        $this->info("Officer {$officer->badge_id} saved with clearance {$level} (".Clearance::from($level)->label().').');

        return self::SUCCESS;
    }
}
