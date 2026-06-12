#!/bin/bash
set -e

cd /var/www/html

echo "→ Preparing UniMed backend…"

# Dependencies (skip if already present via mounted vendor).
if [ ! -f vendor/autoload.php ]; then
  echo "→ Installing composer dependencies…"
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Environment file.
if [ ! -f .env ]; then
  echo "→ Creating .env from .env.docker"
  cp .env.docker .env
fi

# App key.
if ! grep -q "^APP_KEY=base64:" .env; then
  php artisan key:generate --force --no-interaction
fi

# Wait for PostgreSQL.
echo "→ Waiting for PostgreSQL at ${DB_HOST:-postgres}:${DB_PORT:-5432}…"
until php -r "exit(@fsockopen(getenv('DB_HOST') ?: 'postgres', (int)(getenv('DB_PORT') ?: 5432)) ? 0 : 1);"; do
  sleep 2
done
echo "→ PostgreSQL is up."

# Migrate, and seed only on a fresh database.
php artisan migrate --force
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tr -dc '0-9' || echo "")
if [ -z "$USER_COUNT" ] || [ "$USER_COUNT" = "0" ]; then
  echo "→ Seeding demo data…"
  php artisan db:seed --force
else
  echo "→ Database already seeded ($USER_COUNT users) — skipping."
fi

php artisan storage:link 2>/dev/null || true

echo "→ UniMed backend ready."
exec "$@"
