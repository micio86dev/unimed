<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Seed the database only when it is empty.
 *
 * Used in the production start command so a freshly-provisioned environment
 * gets the demo dataset automatically, while restarts and redeploys leave the
 * existing data untouched (the regular seeders are not idempotent).
 */
class SeedIfEmpty extends Command
{
    protected $signature = 'db:seed-if-empty';

    protected $description = 'Run the database seeders only if no users exist yet';

    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->info('Database already seeded — skipping.');

            return self::SUCCESS;
        }

        $this->info('Empty database detected — seeding demo data…');
        $this->call('db:seed', ['--force' => true]);

        return self::SUCCESS;
    }
}
