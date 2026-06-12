<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Permissions granted to administrators.
     */
    public const ADMIN_PERMISSIONS = [
        'manage users',
        'manage subjects',
        'manage questions',
        'manage quizzes',
        'view admin analytics',
    ];

    /**
     * Permissions granted to students.
     */
    public const STUDENT_PERMISSIONS = [
        'take quizzes',
        'view rankings',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = [...self::ADMIN_PERMISSIONS, ...self::STUDENT_PERMISSIONS];

        foreach ($all as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $admin = Role::findOrCreate(UserRole::Admin->value, 'web');
        $student = Role::findOrCreate(UserRole::Student->value, 'web');

        $admin->syncPermissions([...self::ADMIN_PERMISSIONS, ...self::STUDENT_PERMISSIONS]);
        $student->syncPermissions(self::STUDENT_PERMISSIONS);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
