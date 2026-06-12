<?php

declare(strict_types=1);

use App\Models\Subject;

it('forbids students from reaching admin endpoints', function (string $method, string $uri): void {
    actingAsStudent();

    $this->json($method, $uri)->assertForbidden();
})->with([
    ['GET', '/api/admin/analytics'],
    ['GET', '/api/admin/questions'],
    ['POST', '/api/admin/questions'],
    ['GET', '/api/admin/users'],
    ['POST', '/api/admin/quizzes'],
]);

it('allows admins to reach admin endpoints', function (): void {
    actingAsAdmin();

    $this->getJson('/api/admin/analytics')->assertOk();
    $this->getJson('/api/admin/users')->assertOk();
    $this->getJson('/api/admin/questions')->assertOk();
});

it('lets any authenticated user read subjects', function (): void {
    Subject::factory()->count(3)->create();
    actingAsStudent();

    $this->getJson('/api/subjects')->assertOk()->assertJsonStructure(['data']);
});

it('blocks disabled users mid-session via the active middleware', function (): void {
    $user = createStudent();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/subjects')->assertOk();

    $user->update(['is_active' => false]);

    // Simulate a fresh request: clear the guard's cached (now-stale) user so the
    // token is re-resolved from the database, exactly as it would be in prod.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/subjects')->assertForbidden();
});
