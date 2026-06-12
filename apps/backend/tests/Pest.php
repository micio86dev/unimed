<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Domain test helpers
|--------------------------------------------------------------------------
*/

function seedRoles(): void
{
    (new RolePermissionSeeder())->run();
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createAdmin(array $attributes = []): User
{
    seedRoles();
    $user = User::factory()->create($attributes);
    $user->assignRole('admin');

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createStudent(array $attributes = []): User
{
    seedRoles();
    $user = User::factory()->create($attributes);
    $user->assignRole('student');

    return $user;
}

function actingAsAdmin(array $attributes = []): User
{
    $user = createAdmin($attributes);
    Sanctum::actingAs($user);

    return $user;
}

function actingAsStudent(array $attributes = []): User
{
    $user = createStudent($attributes);
    Sanctum::actingAs($user);

    return $user;
}
