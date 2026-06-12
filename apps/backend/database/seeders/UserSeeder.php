<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public const STUDENT_COUNT = 50;

    public function run(): void
    {
        // Demo admin — credentials surfaced on the login screen.
        $admin = User::updateOrCreate(
            ['email' => 'admin@unimed.app'],
            [
                'name' => 'Alessandra Conti',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles([UserRole::Admin->value]);

        // Known demo student.
        $demo = User::updateOrCreate(
            ['email' => 'student@unimed.app'],
            [
                'name' => 'Marco Rossi',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $demo->syncRoles([UserRole::Student->value]);

        // Remaining students.
        $existing = User::role(UserRole::Student->value)->count();
        $toCreate = max(0, self::STUDENT_COUNT - $existing);

        User::factory()
            ->count($toCreate)
            ->create(['password' => Hash::make('password')])
            ->each(function (User $user, int $index): void {
                $user->syncRoles([UserRole::Student->value]);
                // A few inactive accounts for the admin "disable user" demo.
                if ($index % 17 === 0) {
                    $user->update(['is_active' => false]);
                }
            });
    }
}
