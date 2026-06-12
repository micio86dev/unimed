# UniMed — Architecture

UniMed is a university admission-test prep platform. Students take realistic,
timed quizzes drawn from a curated question bank, review per-question results,
and compete on a leaderboard; administrators manage the content catalogue
(subjects, questions, quizzes), user accounts, and platform analytics.

This document describes the system at a high level, the backend and frontend
internals, the API conventions, and an end-to-end walkthrough of the core
"student takes a quiz" flow.

---

## 1. High-level overview

UniMed is a **decoupled SPA + JSON API**:

- **Backend** — a **Laravel 12** REST API (`apps/backend`) that owns all
  business logic, persistence, scoring, and authorization.
- **Frontend** — a **Nuxt 4** single-page application (`apps/frontend`,
  `ssr: false`) that consumes the API over HTTPS using bearer tokens.

The two halves are independently deployable and live on different domains
(frontend on Vercel, backend on Railway). Because authentication is
**token-based** (Sanctum personal access tokens) rather than cookie/session
based, there is no cross-domain cookie or CSRF friction.

### Monorepo layout

```
unimed/
├── apps/
│   ├── backend/         # Laravel 12 API
│   │   ├── app/         # Application code (see §2)
│   │   ├── routes/      # api.php, web.php, console.php
│   │   ├── database/    # migrations, seeders, factories
│   │   ├── config/      # cors.php, sanctum.php, permission.php, …
│   │   └── railway.json # Railway deploy config
│   └── frontend/        # Nuxt 4 SPA
│       ├── app/         # pages, components, stores, composables (see §3)
│       ├── nuxt.config.ts
│       └── vercel.json  # Vercel deploy config
├── docker/              # Local Docker images (php, nginx, frontend)
├── docker-compose.yml   # Local production-like stack
├── docs/                # This documentation
└── .github/workflows/   # ci.yml + deploy.yml
```

The repository is a **pnpm workspace** (`pnpm-workspace.yaml`) with shared
tooling (Biome for the JS/TS side, Husky git hooks). PHP dependencies are
managed per-app with Composer.

---

## 2. Backend architecture (`apps/backend/app`)

The backend follows a layered design that keeps the **scoring engine pure**,
**side-effecting orchestration in Actions/Services**, and the **HTTP layer
thin**.

### 2.1 Models (`app/Models`)

Eloquent models map directly to the domain tables (see `docs/DATABASE.md`):

| Model | Notes |
| --- | --- |
| `User` | `Authenticatable`; uses `HasApiTokens` (Sanctum), `HasRoles` (Spatie). Relations: `attempts()`, `ranking()`, `activityLogs()`. Helpers `isAdmin()`, `isStudent()`. |
| `Subject` | Resolved by `slug` route key. `hasMany` questions. |
| `Question` | Casts `type → QuestionType`, `difficulty → Difficulty`. Relations: `subject`, `answers` (ordered), `correctAnswers`, `quizzes` (pivot). Scopes `active()`, `search()`. Exposes `correctAnswerIds()` — the sorted list of correct answer ids used by the scoring engine. |
| `QuestionAnswer` | Casts `is_correct → bool`. Belongs to a question. |
| `Quiz` | Resolved by `slug`. `belongsToMany` questions through `quiz_questions` (ordered by pivot `position`). Casts `settings → array`. Scope `published()`. |
| `QuizAttempt` | Casts `status → AttemptStatus`. Owns aggregate stats (`correct_count`, `percentage`, `points`, …). Relations: `user`, `quiz`, `answers`. Scope `completed()`. |
| `QuizAttemptAnswer` | Casts `selected_answer_ids → array`. One row per question per attempt; updated in place by autosave. |
| `Ranking` | One-to-one with `User`; the denormalised leaderboard row. |
| `ActivityLog` | Append-only audit row (`UPDATED_AT = null`). Morphs to a subject. |

### 2.2 Enums (`app/Enums`)

Backed string enums carry both data and behaviour:

- **`Difficulty`** (`easy`, `medium`, `hard`) — exposes `label()` and
  `weight()`. The weight (`easy = 1`, `medium = 2`, `hard = 3`) is the
  multiplier used by the scoring engine.
- **`QuestionType`** (`single`, `multiple`) — `allowsMultiple()` distinguishes
  single- from multiple-choice.
- **`AttemptStatus`** (`in_progress`, `completed`, `abandoned`) — `isFinal()`
  guards re-submission.
- **`UserRole`** (`admin`, `student`) — the role names used by Spatie and the
  admin `Gate::before` bypass.

Each enum provides a static `values()` helper consumed by Form Request
validation rules (e.g. `Rule::in(Difficulty::values())`).

### 2.3 Domain (`app/Domain/Quiz`) — the scoring engine

`QuizScoringService` is a **pure function** over data: given a collection of
questions (with answers loaded) and a map of `question_id → selected answer
ids`, it returns an immutable `ScoreResult`. It performs **no database access
and no persistence**, which makes it trivial to unit test.

The scoring rule is intentionally strict:

> A question is **correct only when the set of selected answer ids is exactly
> equal to the set of correct answer ids** (order-independent). This holds for
> both single- and multiple-choice questions — partial selections never count.

Points are awarded per correct question:

```
points = QuizScoringService::POINTS_PER_WEIGHT (10) × difficulty weight
       → easy = 10, medium = 20, hard = 30
```

Selections are normalised before comparison (cast to int, drop non-positive,
dedupe, sort), so `[3, 1]` and `[1, 3]` are equivalent. The service produces:

- `ScoreResult` — totals (`totalQuestions`, `correctCount`, `incorrectCount`,
  `unansweredCount`), `percentage`, `points`, and a map of per-question
  `QuestionOutcome` values.
- `QuestionOutcome` — `questionId`, `isAnswered`, `isCorrect`, `pointsAwarded`.

### 2.4 Actions (`app/Actions/Quiz`)

Actions are single-purpose orchestrators that wrap the domain logic in a
transaction and handle persistence:

- **`StartQuizAttempt`** — returns the existing in-progress attempt if one
  exists (resume semantics); otherwise creates a new `QuizAttempt` plus one
  **placeholder** `quiz_attempt_answers` row per quiz question, so autosave can
  update rows in place rather than insert.
- **`SubmitQuizAttempt`** — loads the attempt's answers and quiz questions,
  delegates to `QuizScoringService`, then in a transaction persists
  per-question `is_correct`, the attempt's aggregate stats, status
  (`completed`), elapsed time, and finally calls `RankingService` to refresh
  the student's leaderboard entry.

### 2.5 Services (`app/Services`)

- **`QuizService`** — quiz creation/editing. `createManual()` builds a quiz
  from an explicit ordered question list; `generate()` auto-draws random
  *active* questions matching subject/difficulty filters. `syncQuestions()`
  writes the `quiz_questions` pivot with `position` and refreshes the cached
  `question_count`. Also owns unique slug generation.
- **`RankingService`** — maintains the denormalised `rankings` table.
  `recalculateFor($userId)` recomputes a student's aggregates from their
  *completed* attempts (count, total points, average/best score, average time)
  then `recalculatePositions()` re-ranks everyone by total points → average
  score → average time.
- **`AnalyticsService`** — read-optimised aggregation for dashboards.
  `forStudent()` returns KPIs, per-subject accuracy, recent attempts, and a
  score trend; `forAdmin()` returns platform KPIs, hardest subjects, and a
  14-day attempts trend. Uses a `trueLiteral()` helper so the raw SQL is
  portable between SQLite (`1`) and PostgreSQL (`true`).
- **`ActivityLogger`** — lightweight audit logger writing to `activity_logs`
  (actor, action, description, polymorphic subject, JSON properties, IP).

### 2.6 HTTP layer (`app/Http`)

- **Controllers** (`app/Http/Controllers/Api`) are thin: they validate via Form
  Requests, delegate to Actions/Services, and return Resources or
  `ApiResponse`. Controllers: `Auth/AuthController`, `SubjectController`,
  `QuizController`, `AttemptController`, `RankingController`,
  `AnalyticsController`, `QuestionController`, `UserController`,
  `UploadController`.
- **Form Requests** (`app/Http/Requests`) centralise validation — e.g.
  `StoreQuestionRequest` enforces 2–6 answers and "exactly one correct for
  single choice", `StoreQuizRequest` branches on `mode` (`manual` vs `auto`),
  `SaveAnswerRequest` validates the autosave payload.
- **API Resources** (`app/Http/Resources`) shape JSON output. Note the
  deliberate split: `AnswerResource` exposes `is_correct` and is only used in
  admin/review contexts — it is **never** used when serving a quiz for a
  student to take.
- **Middleware** — `EnsureUserIsActive` (alias `active`) rejects requests from
  disabled accounts and revokes the presented token. Spatie's `role`,
  `permission`, and `role_or_permission` middleware are aliased in
  `bootstrap/app.php`.
- **Support** —
  - `ApiResponse` — consistent non-resource JSON envelopes (`success()`,
    `message()`, `error()`).
  - `AttemptPresenter` — builds the two composite attempt payloads. The
    **`take()`** payload is sanitised (no correct answers, no explanations);
    the **`result()`** payload reveals everything for post-submission review,
    including a per-subject breakdown.

### 2.7 Authentication & authorization

- **Auth = Laravel Sanctum personal access tokens.** On login the API issues a
  plain-text bearer token (12h, or 30 days with `remember`). Clients send it as
  `Authorization: Bearer <token>`. This is **stateless and cross-domain
  friendly** — no session cookies or CSRF token exchange. The configuration is
  token-only: `bootstrap/app.php` adds no stateful session layer.
- **Roles & permissions = `spatie/laravel-permission`.** Roles `admin` and
  `student`; permissions seeded by `RolePermissionSeeder`
  (admins: `manage users/subjects/questions/quizzes`, `view admin analytics`,
  plus the student permissions; students: `take quizzes`, `view rankings`).
- **Admin bypass.** `AppServiceProvider::boot()` registers a `Gate::before`
  hook: any user with the `admin` role implicitly passes every authorization
  check. The same provider configures the per-user API rate limiter and points
  password-reset links at the SPA (`FRONTEND_URL/reset-password`).

---

## 3. Frontend architecture (`apps/frontend/app`)

A **Nuxt 4 SPA** (`ssr: false`). Rendering as a client-side app keeps the
auth model simple: the bearer token lives in the browser and is attached to
every API call.

### 3.1 State — Pinia stores (`app/stores`)

- **`stores/auth.ts`** owns the session: `user`, `permissions`, `ready`, and
  derived `isAuthenticated` / `isAdmin` / `isStudent` / `can(permission)`.
  `login()` stores the token, hydrates the user, and captures permissions from
  the login response (no extra round-trip). `init()` hydrates the token from
  its cookie on app start and resolves the user via `/auth/me`.
- **`stores/quiz.ts`** owns the **live attempt**. This is the most stateful part
  of the app:
  - Holds the loaded `questions`, `currentIndex`, and a reactive `selections`
    map (`question_id → answer ids[]`).
  - **Navigation** — `goTo()`, `next()`, `prev()`, plus `answeredCount`,
    `remainingCount`, and `progress` computeds.
  - **Timer** — a client-side `setInterval` tracks `elapsedSeconds`; on resume
    it reconstructs elapsed time from the server `started_at`. `remainingSeconds`
    is derived from the quiz time limit and auto-stops at zero.
  - **Autosave** — `selectAnswer()` updates the local selection and fires
    `saveAnswer()`, which `PATCH`es the single answer to the API. Failures keep
    the local selection and are retried on the next change or on submit.
  - `submit()` posts the elapsed time and returns the scored `ResultPayload`.

### 3.2 Composables (`app/composables`)

- **`useApi.ts`** — the API client. `useApi()` returns a pre-configured
  `$fetch` instance pointed at `runtimeConfig.public.apiBase`; it requests
  JSON, attaches `Authorization: Bearer <token>`, and signs the user out on a
  `401`. Helpers `apiErrorMessage()` and `apiValidationErrors()` unwrap the
  API's error envelope. Token helpers live here too (see §3.6).
- **`useToast.ts`** — a minimal global toast queue (`success`/`error`/`info`)
  rendered by `<AppToaster />`.

### 3.3 Route middleware (`app/middleware`)

- `auth.ts` — redirects unauthenticated users to `/login` (preserving a
  `redirect` query).
- `guest.ts` — bounces already-authenticated users to `/dashboard` (or
  `/admin`).
- `admin.ts` — requires the `admin` role; otherwise redirects to `/dashboard`.

Pages opt in via `definePageMeta({ middleware, layout })`. The
`plugins/auth.client.ts` plugin runs `auth.init()` once before the first route
renders, so middleware can rely on the auth state being ready.

### 3.4 Layouts (`app/layouts`)

- `default.vue` — the student shell (Dashboard, Quizzes, Rankings, My
  attempts) wrapping the `<AppShell>` component.
- `admin.vue` — the administration shell (Overview, Questions, Quizzes, Users).
- `auth.vue` — the split-screen sign-in layout with the brand panel.

The active quiz page (`pages/attempt/[id].vue`) uses `layout: false` for a
distraction-free exam view.

### 3.5 Design system & UI

- **Tailwind CSS v4 token system** (`app/assets/css/main.css`). Design tokens
  are declared as CSS custom properties on `:root` (and overridden under
  `.dark`) following the shadcn-vue convention — `--background`, `--primary`
  (brand blue `#0F5EFF`), `--muted`, `--destructive`, `--success`, etc., plus
  per-subject accent colours that mirror the backend seed. They are exposed to
  Tailwind via the `@theme inline` block, so utilities like `bg-primary` and
  `text-muted-foreground` resolve to the tokens.
- **UI primitives** (`app/components/ui`) are **shadcn-vue-style** building
  blocks — `Button`, `Card` (+ parts), `Input`, `Select`, `Modal`, `Badge`,
  `Progress`, `Switch`, `Skeleton`, `Spinner`, etc. — built with
  `class-variance-authority` for variants and `cn()` (clsx + tailwind-merge)
  for class merging.
- **Charts** (`app/components/charts`) are **hand-built SVG/markup** with no
  charting dependency: `BarChart`, `LineChart`, and `RadialScore` (an SVG
  ring drawn from `stroke-dasharray`/`-dashoffset`).

### 3.6 Auth token persistence

The token is held in two places:

- **`useState('unimed_token')`** — the in-memory source of truth, set
  synchronously on login so the *next* request already carries it.
- **A cookie of the same name** (30-day max-age, `sameSite: lax`, `secure` in
  production) — used only to persist the token across page reloads.

On boot, `auth.init()` copies the cookie value into `useState` if the in-memory
slot is empty, then validates it with `/auth/me`. The `useApi` `401` handler
clears both on token expiry/revocation.

---

## 4. API design conventions

- **REST under `/api`** (`routes/api.php`), versionless. Resources use plural
  nouns; admin-only writes are namespaced under `/api/admin`.
- **JSON envelopes.** Successful non-collection responses use
  `{ message?, data, meta? }` via `ApiResponse::success()`. Collections use
  Laravel API Resource collections, which add pagination `meta`/`links`. Plain
  acknowledgements use `{ message }`.
- **API Resources** shape every entity payload, keeping serialization out of
  controllers and enforcing the take-vs-review answer split.
- **Form Request validation** for every write endpoint; validation failures
  return `422` with an `errors` map keyed by field.
- **Consistent error rendering** is configured in `bootstrap/app.php`:
  `ModelNotFoundException`/`NotFoundHttpException` → `404 {message}`,
  `AuthenticationException` → `401 {message}`, and all `api/*` requests render
  JSON.
- **Rate limiting.** The global `api` limiter is **per-user (or per-IP) at
  120 req/min** (`AppServiceProvider`); auth endpoints add tighter throttles
  (e.g. `throttle:10,1` on login, `throttle:5,1` on forgot-password).
- **CORS** allowed origins come from `FRONTEND_URL` (`config/cors.php`).

---

## 5. Request lifecycle — "student takes a quiz"

The end-to-end flow, from the SPA through the API to a scored result:

1. **Start the attempt.** The quiz store calls
   `POST /api/attempts { quiz_id }`. `AttemptController@store` loads the
   *published* quiz, rejects empty quizzes, and delegates to
   **`StartQuizAttempt`**, which creates (or resumes) a `QuizAttempt` and seeds
   one placeholder `quiz_attempt_answers` row per question. The response is the
   sanitised **take** payload from `AttemptPresenter::take()` — questions and
   options, but no correct answers. The store loads it and starts the timer.

2. **Answer & autosave.** As the student selects options,
   `quizStore.selectAnswer()` updates the local `selections` map and fires
   `PATCH /api/attempts/{attempt}/answers { question_id, selected_answer_ids }`.
   `AttemptController@saveAnswer` verifies ownership and that the attempt is
   in progress, then updates the matching answer row in place
   (`selected_answer_ids`, `is_answered`, `answered_at`). The student can
   navigate freely; each change is saved independently.

3. **Submit.** `POST /api/attempts/{attempt}/submit { time_spent_seconds }`.
   `AttemptController@submit` confirms ownership/in-progress, then calls
   **`SubmitQuizAttempt`**, which:
   - feeds the saved selections and quiz questions to **`QuizScoringService`**
     (exact-set-match scoring; points = 10 × difficulty weight);
   - persists per-question `is_correct`, the attempt's aggregate stats,
     `status = completed`, elapsed time, and `completed_at`;
   - calls **`RankingService::recalculateFor()`** to refresh the leaderboard.

4. **Scored result.** The submit response (and later
   `GET /api/attempts/{attempt}/result`) returns `AttemptPresenter::result()` —
   the attempt summary, a per-subject breakdown, and a full per-question review
   that *now* reveals correct answer ids and explanations. The SPA renders this
   on the results page, and the new stats flow into the student's dashboard
   analytics and ranking.
