<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a realistic, demo-ready dataset.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            SubjectSeeder::class,
            UserSeeder::class,
            QuestionSeeder::class,
            QuizSeeder::class,
            AttemptSeeder::class,
        ]);
    }
}
