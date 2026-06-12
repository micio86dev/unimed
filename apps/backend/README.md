# UniMed — Backend

Laravel 12 REST API for the UniMed admission-test platform.

- **Auth**: Laravel Sanctum (Bearer personal access tokens) + spatie/laravel-permission roles
- **Architecture**: DDD-lite — `app/Domain` (pure scoring engine), `app/Actions`, `app/Services`, thin controllers in `app/Http/Controllers/Api`
- **Database**: PostgreSQL 17 (portable migrations; tests run on in-memory SQLite)

## Develop

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve        # → http://localhost:8000
```

## Test

```bash
./vendor/bin/pest                 # full suite
./vendor/bin/pest --coverage      # with coverage (requires pcov/xdebug)
```

See the [root README](../../README.md), [architecture](../../docs/ARCHITECTURE.md), [database](../../docs/DATABASE.md) and [API reference](../../docs/API.md).
