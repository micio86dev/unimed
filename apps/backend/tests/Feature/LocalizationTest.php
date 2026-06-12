<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;

it('returns English API messages by default', function (): void {
    $user = createStudent(['email' => 'leo@unimed.app', 'password' => Hash::make('password')]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'leo@unimed.app',
        'password' => 'password',
    ]);

    expect($response->json('message'))->toBe('Signed in successfully.');
});

it('returns Italian API messages when Accept-Language is it', function (): void {
    createStudent(['email' => 'leo@unimed.app', 'password' => Hash::make('password')]);

    $response = $this->withHeader('Accept-Language', 'it')->postJson('/api/auth/login', [
        'email' => 'leo@unimed.app',
        'password' => 'password',
    ]);

    expect($response->json('message'))->toBe('Accesso effettuato con successo.');
});

it('localizes validation errors via the ?lang override', function (): void {
    createStudent(['email' => 'leo@unimed.app', 'password' => Hash::make('password')]);

    $response = $this->postJson('/api/auth/login?lang=it', [
        'email' => 'leo@unimed.app',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    expect($response->json('errors.email.0'))->toBe('Le credenziali inserite non corrispondono ai nostri dati.');
});
