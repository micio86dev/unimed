<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function (): void {
    seedRoles();
});

it('registers a student, activates the account and logs them in immediately', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Giulia Bianchi',
        'email' => 'giulia@unimed.app',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['message', 'data' => ['token', 'user' => ['id', 'email', 'roles'], 'permissions']]);

    expect($response->json('data.user.roles'))->toContain('student');
    expect($response->json('data.user.is_active'))->toBeTrue();
    expect($response->json('data.token'))->not->toBeEmpty();

    $user = User::where('email', 'giulia@unimed.app')->first();
    expect($user)->not->toBeNull();
    expect($user->is_active)->toBeTrue();
    expect($user->last_login_at)->not->toBeNull();
});

it('lets the freshly-registered student use their token straight away', function (): void {
    $token = $this->postJson('/api/auth/register', [
        'name' => 'Giulia Bianchi',
        'email' => 'giulia@unimed.app',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->json('data.token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', 'giulia@unimed.app');
});

it('rejects a duplicate email', function (): void {
    User::factory()->create(['email' => 'taken@unimed.app']);

    $this->postJson('/api/auth/register', [
        'name' => 'Someone',
        'email' => 'taken@unimed.app',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('requires a confirmed password', function (): void {
    $this->postJson('/api/auth/register', [
        'name' => 'Someone',
        'email' => 'new@unimed.app',
        'password' => 'password123',
        'password_confirmation' => 'mismatch',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('returns a localized message in Italian', function (): void {
    $response = $this->withHeader('Accept-Language', 'it')->postJson('/api/auth/register', [
        'name' => 'Giulia Bianchi',
        'email' => 'giulia@unimed.app',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    expect($response->json('message'))->toBe('Benvenuto su UniMed! Il tuo account è pronto.');
});
