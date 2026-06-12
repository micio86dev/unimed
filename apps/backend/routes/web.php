<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Headless API — the UI is the separate Nuxt app. Root returns a small JSON
// banner; the application health check lives at GET /up (see bootstrap/app.php).
Route::get('/', fn (): array => [
    'name' => config('app.name'),
    'service' => 'api',
    'status' => 'ok',
]);
