<?php

declare(strict_types=1);

use App\Models\User;

it('seeds the database when it is empty', function (): void {
    expect(User::query()->count())->toBe(0);

    $this->artisan('db:seed-if-empty')
        ->expectsOutputToContain('Empty database detected')
        ->assertExitCode(0);

    expect(User::query()->count())->toBeGreaterThan(0);
});

it('leaves an already-populated database untouched', function (): void {
    $existing = createStudent(['email' => 'only@unimed.app']);

    $this->artisan('db:seed-if-empty')
        ->expectsOutputToContain('already seeded')
        ->assertExitCode(0);

    expect(User::query()->count())->toBe(1);
    expect(User::query()->first()->is($existing))->toBeTrue();
});
