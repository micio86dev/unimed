# UniMed — Deployment

UniMed deploys as two independent services:

- **Backend** (Laravel API) → **Railway** (NIXPACKS build).
- **Frontend** (Nuxt SPA) → **Vercel** (static `.output/public`).

Both are deployed automatically by GitHub Actions after CI passes, and both can
also be run together locally with Docker Compose.

---

## 1. Backend → Railway

Configured by `apps/backend/railway.json`:

- **Builder:** `NIXPACKS` (no Dockerfile required — Railway detects the PHP app).
- **Start command:** runs migrations on every deploy, then serves the app:

  ```
  php artisan migrate --force && php artisan serve --host 0.0.0.0 --port ${PORT:-8000}
  ```

- **Health check:** `GET /up` (Laravel's built-in health route, registered in
  `bootstrap/app.php`), with a 60s timeout.
- **Restart policy:** `ON_FAILURE`, up to 3 retries.

### Required environment variables

| Variable | Purpose |
| --- | --- |
| `APP_KEY` | Laravel encryption key (`php artisan key:generate --show`). |
| `APP_URL` | Public URL of the API service. |
| `APP_ENV` / `APP_DEBUG` | `production` / `false`. |
| `FRONTEND_URL` | The Vercel SPA origin — used for password-reset links **and** as the allowed CORS origin. |
| `DB_CONNECTION` | `pgsql`. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Provided by the Railway **PostgreSQL** plugin. |
| `CACHE_STORE` | `database` (the default — no Redis required), or `redis`. |
| `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | Only if using Redis for cache/queue. |
| `SESSION_DRIVER` | Not session-critical (token auth), commonly `database`/`file`. |
| `SANCTUM_STATEFUL_DOMAINS` | Optional — token auth does not need stateful domains; only set if you also use cookie SPA auth. |
| `MAIL_*` | SMTP settings for password-reset emails. |

> **CORS.** `config/cors.php` builds `allowed_origins` from `FRONTEND_URL`
> (plus localhost fallbacks for development). Set `FRONTEND_URL` to the exact
> Vercel origin so the SPA can call the API. `supports_credentials` is enabled.

> **Database.** The default connection (`config/database.php`) is `sqlite`;
> production must set `DB_CONNECTION=pgsql` and the `DB_*` values from the
> Railway Postgres plugin. The default cache store (`config/cache.php`) is
> `database`, so a Redis instance is optional.

---

## 2. Frontend → Vercel

Configured by `apps/frontend/vercel.json`:

- **Framework preset:** `nuxt`.
- **Install:** `pnpm install --frozen-lockfile`.
- **Build:** `pnpm build`.
- **Output directory:** `.output/public` (the Nuxt SPA build — `ssr: false`).

### Required environment variable

| Variable | Purpose |
| --- | --- |
| `NUXT_PUBLIC_API_BASE` | The Railway API URL **with the `/api` suffix**, e.g. `https://<railway-app>/api`. Read by `useApi()` via `runtimeConfig.public.apiBase` (defaults to `http://localhost:8000/api`). |

---

## 3. CI/CD (GitHub Actions)

### `.github/workflows/ci.yml`

Runs on pushes to `main` and on pull requests (Node 24, PHP 8.4). Three jobs:

1. **`frontend`** — `apps/frontend`: Biome lint (`biome check .`), Nuxt
   `typecheck`, Vitest unit tests (`pnpm test`), and a production `build`.
2. **`backend`** — `apps/backend`: Composer install, prepare `.env`
   (`key:generate`), then **Pest with coverage gated at `--min=70`**
   (`pcov`).
3. **`e2e`** — depends on `frontend` + `backend`. Spins up a **PostgreSQL 17
   service container**, migrates and seeds the API, runs `php artisan serve`,
   installs Playwright Chromium, and runs the Playwright suite against the live
   API; uploads the `playwright-report` artifact.

### `.github/workflows/deploy.yml`

Triggered by `workflow_run` when **CI** completes **successfully** on `main`.
Two independent jobs, each gated on its platform secret (so forks/PRs without
secrets no-op gracefully rather than failing):

- **`deploy-backend`** (Railway) — skips if `RAILWAY_TOKEN` is unset; otherwise
  installs the Railway CLI and runs `railway up --service backend --detach`.
- **`deploy-frontend`** (Vercel) — skips if `VERCEL_TOKEN` is unset; otherwise
  `vercel pull` / `vercel build --prod` / `vercel deploy --prebuilt --prod` from
  `apps/frontend`, using `VERCEL_ORG_ID` and `VERCEL_PROJECT_ID`.

**Required deployment secrets:** `RAILWAY_TOKEN`, `VERCEL_TOKEN`,
`VERCEL_ORG_ID`, `VERCEL_PROJECT_ID`.

---

## 4. Local production-like run

Run the full stack locally with Docker Compose:

```bash
docker compose up -d
```

`docker-compose.yml` brings up:

- **`postgres`** (Postgres 17) — db/user/password `unimed` / `unimed` / `secret`,
  on `:5432`.
- **`redis`** (Redis 7) on `:6379`.
- **`php`** (PHP 8.4 FPM, `docker/php/Dockerfile`) serving the Laravel app.
- **`nginx`** (`docker/nginx/default.conf`) exposing the API on
  **`http://localhost:8000`**.
- **`frontend`** (Node 24, `docker/frontend/Dockerfile`) running the Nuxt dev
  server on **`http://localhost:3000`**, pointed at the API via
  `NUXT_PUBLIC_API_BASE=http://localhost:8000/api`.
- **`mailpit`** — captured outbound mail with a web UI on
  **`http://localhost:8025`** (SMTP `:1025`).

This mirrors the production topology (Postgres-backed API + SPA on separate
origins, token auth, CORS) on a single machine.
