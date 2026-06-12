<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('logs in with valid credentials and returns a token', function (): void {
    $user = createStudent(['email' => 'jane@unimed.app', 'password' => Hash::make('password')]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'jane@unimed.app',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'data' => ['token', 'user' => ['id', 'email', 'roles']]]);

    expect($response->json('data.user.roles'))->toContain('student');
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects invalid credentials with a 422', function (): void {
    createStudent(['email' => 'jane@unimed.app', 'password' => Hash::make('password')]);

    $this->postJson('/api/auth/login', [
        'email' => 'jane@unimed.app',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('blocks disabled accounts from logging in', function (): void {
    createStudent(['email' => 'disabled@unimed.app', 'password' => Hash::make('password'), 'is_active' => false]);

    $this->postJson('/api/auth/login', [
        'email' => 'disabled@unimed.app',
        'password' => 'password',
    ])->assertStatus(403);
});

it('issues a longer-lived token when remember is set', function (): void {
    createStudent(['email' => 'jane@unimed.app', 'password' => Hash::make('password')]);

    $this->postJson('/api/auth/login', [
        'email' => 'jane@unimed.app',
        'password' => 'password',
        'remember' => true,
    ])->assertOk();

    $token = \Laravel\Sanctum\PersonalAccessToken::first();
    expect($token->expires_at)->not->toBeNull()
        ->and($token->expires_at->isAfter(now()->addDays(20)))->toBeTrue();
});

it('returns the authenticated user and permissions from /me', function (): void {
    $admin = actingAsAdmin();

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.email', $admin->email)
        ->assertJsonStructure(['data' => ['user', 'permissions']]);

    expect($this->getJson('/api/auth/me')->json('data.permissions'))->toContain('manage questions');
});

it('rejects unauthenticated access to protected routes', function (): void {
    $this->getJson('/api/auth/me')->assertUnauthorized();
    $this->getJson('/api/subjects')->assertUnauthorized();
});

it('revokes the token on logout', function (): void {
    $user = createStudent();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertOk();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

it('accepts a forgot-password request without leaking account existence', function (): void {
    createStudent(['email' => 'jane@unimed.app']);

    $this->postJson('/api/auth/forgot-password', ['email' => 'jane@unimed.app'])->assertOk();
    $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@unimed.app'])->assertOk();
});
