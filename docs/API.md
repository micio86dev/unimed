# UniMed — API Reference

The backend exposes a JSON REST API under the `/api` prefix
(`apps/backend/routes/api.php`). This document lists every endpoint grouped by
area, with the required auth/role, request fields, and response shape.

## Conventions

- **Base URL:** `<API host>/api` (e.g. `https://<railway-app>/api`).
- **Authentication:** All endpoints except the public auth routes require a
  Sanctum bearer token:

  ```
  Authorization: Bearer <token>
  Accept: application/json
  ```

  Authenticated routes also pass through the `active` middleware, which rejects
  (and logs out) disabled accounts with `403`.
- **Admin routes** are namespaced under `/api/admin` and additionally require
  the `admin` role (`role:admin` middleware). Administrators implicitly pass
  every authorization check via a `Gate::before` bypass.
- **Response envelope:** single-object responses use
  `{ message?, data, meta? }`; list endpoints return Laravel paginated
  collections (`{ data: [...], links, meta }`); plain acknowledgements return
  `{ message }`.
- **Errors:** validation failures → `422 { message, errors }`; auth failures →
  `401 { message }`; not found → `404 { message }`; forbidden / state conflicts
  → `403` / `409` / `422 { message }`.
- **Rate limits:** global `120 req/min` per user/IP; auth endpoints are
  throttled tighter (login `10/min`, forgot-password `5/min`,
  reset-password `10/min`).

---

## Auth

Prefix: `/api/auth`.

### `POST /auth/login`
Public · throttle `10/min`. Authenticate and issue a token.

- **Request:** `email` (required), `password` (required), `remember`
  (optional bool — extends token life to 30 days, else 12 hours).
- **Response `200`:** `{ message, data: { token, user, permissions[] } }`.
  Disabled accounts return `403`; bad credentials return `422`.

### `POST /auth/forgot-password`
Public · throttle `5/min`. Send a password-reset link (pointing at the SPA).

- **Request:** `email` (required).
- **Response `200`:** `{ message }` (generic, to avoid user enumeration).

### `POST /auth/reset-password`
Public · throttle `10/min`. Reset a password using an emailed token.

- **Request:** `token`, `email`, `password`, `password_confirmation`.
- **Response `200`:** `{ message }`. Invalid token/email → `422`.

### `POST /auth/logout`
Auth required. Revoke the token used for the current request.

- **Response `200`:** `{ message }`.

### `GET /auth/me`
Auth required. Return the current user with roles and permissions.

- **Response `200`:** `{ data: { user, permissions[] } }`.

---

## Subjects

### `GET /subjects`
Auth required. List all subjects (ordered by `position`) with their **active**
question counts.

- **Response `200`:** collection of subject resources
  (`id, name, slug, description, color, icon, position, questions_count`).

---

## Quizzes (catalogue — read)

### `GET /quizzes`
Auth required. Paginated quiz catalogue. Students see **published** quizzes
only; admins may pass `?scope=all` to include drafts.

- **Query:** `scope` (`all`, admin only), `search`, `difficulty`, `per_page`
  (default `12`).
- **Response `200`:** paginated collection of quiz resources (each with
  `attempts_count`).

### `GET /quizzes/{quiz}`
Auth required. Show one quiz (resolved by **slug**). Unpublished quizzes are
`404` for non-admins. Only admins receive the full embedded question set
(including correct answers).

- **Response `200`:** `{ data: <quiz resource> }`.

---

## Attempts (core student flow)

### `GET /attempts`
Auth required. The current student's own attempt history (paginated).

- **Query:** `status` (filter), `per_page` (default `10`).
- **Response `200`:** paginated collection of attempt summary resources.

### `POST /attempts`
Auth required. Start (or resume) an attempt for a **published** quiz. Quizzes
with no questions return `422`.

- **Request:** `quiz_id` (required, must exist).
- **Response `201`:** `{ message, data: <take payload> }` — the sanitised
  payload (`attempt` summary + `questions` with options but **no correct
  answers**).

### `GET /attempts/{attempt}`
Auth required, owner only. Resume an **in-progress** attempt (returns the take
payload). Already-submitted attempts return `409`.

- **Response `200`:** `{ data: <take payload> }`.

### `PATCH /attempts/{attempt}/answers`
Auth required, owner only, attempt must be in progress. Autosave a single
answer.

- **Request:** `question_id` (required, must be part of the attempt),
  `selected_answer_ids` (array — may be empty to clear).
- **Response `200`:** `{ message, data: { question_id, is_answered } }`.

### `POST /attempts/{attempt}/submit`
Auth required, owner only, attempt must be in progress. Score the attempt and
return the detailed result.

- **Request:** `time_spent_seconds` (optional int; falls back to server-computed
  elapsed time).
- **Response `200`:** `{ message, data: <result payload> }` — attempt summary,
  `subject_breakdown`, and a full per-question `review` (now revealing correct
  answer ids and explanations).

### `GET /attempts/{attempt}/result`
Auth required, owner only. The detailed result of a **completed** attempt.
Non-completed attempts return `409`.

- **Response `200`:** `{ data: <result payload> }`.

---

## Rankings

### `GET /rankings`
Auth required. The leaderboard (students with ≥1 completed quiz), ordered by
`position`, paginated.

- **Query:** `per_page` (default `20`).
- **Response `200`:** paginated collection of ranking resources
  (`position, user_id, name, quizzes_completed, total_points, average_score,
  best_score, average_time_seconds, last_activity_at, is_current_user`).

### `GET /rankings/me`
Auth required. The current student's own ranking entry.

- **Response `200`:** `{ data: { ranking, total_participants } }` (`ranking` is
  `null` if the student has no completed attempts yet).

---

## Analytics

### `GET /analytics/student`
Auth required. KPI + chart payload for the authenticated student's dashboard.

- **Response `200`:** `{ data: { kpis, subject_performance, recent_attempts,
  score_trend } }`.

### `GET /admin/analytics`
Admin role required. Platform-wide analytics for the admin dashboard.

- **Response `200`:** `{ data: { kpis, hardest_subjects, attempts_trend } }`.

---

## Admin — Questions

`apiResource` under `/api/admin/questions`. Admin role required.

### `GET /admin/questions`
Paginated, filterable, searchable list (with subject + answers).

- **Query:** `subject_id`, `difficulty`, `type`, `is_active`, `search`,
  `per_page` (default `15`).
- **Response `200`:** paginated collection of question resources.

### `POST /admin/questions`
Create a question and its answers.

- **Request:** `subject_id`, `type` (`single`/`multiple`), `difficulty`
  (`easy`/`medium`/`hard`), `text`, `explanation?`, `image_path?`, `is_active?`,
  `answers[]` (2–6 items, each `{ text, is_correct }`). Validation enforces
  ≥1 correct answer, and **exactly one** correct for single-choice.
- **Response `201`:** `{ message, data: <question resource> }`.

### `GET /admin/questions/{question}`
Show one question (with subject + answers).

- **Response `200`:** `{ data: <question resource> }`.

### `PUT/PATCH /admin/questions/{question}`
Replace the question and its full answer set (same rules as create).

- **Response `200`:** `{ message, data: <question resource> }`.

### `DELETE /admin/questions/{question}`
Delete a question.

- **Response `200`:** `{ message }`.

---

## Admin — Quizzes (write)

Admin role required (catalogue **reads** are the shared `/quizzes` routes
above).

### `POST /admin/quizzes`
Create a quiz, either manually or auto-generated.

- **Request:** `title`, `description?`, `time_limit_minutes?`, `difficulty?`,
  `is_published?`, `mode` (`manual` | `auto`).
  - **`manual`:** `question_ids[]` (required, ordered, distinct, must exist).
  - **`auto`:** `question_count` (required, 1–120), `subject_ids[]?`,
    `difficulty?` (generation filters).
- **Response `201`:** `{ message, data: <quiz resource> }`.

### `PUT/PATCH /admin/quizzes/{quiz}`
Update quiz metadata and optionally replace its questions.

- **Request:** any of `title`, `description`, `time_limit_minutes`,
  `difficulty`, `is_published`, `question_ids[]` (ordered, distinct).
- **Response `200`:** `{ message, data: <quiz resource> }`.

### `DELETE /admin/quizzes/{quiz}`
Delete a quiz.

- **Response `200`:** `{ message }`.

---

## Admin — Users

`apiResource` under `/api/admin/users` plus a toggle route. Admin role required.

### `GET /admin/users`
Paginated, filterable list (with roles).

- **Query:** `role`, `is_active`, `search` (name/email), `per_page`
  (default `15`).
- **Response `200`:** paginated collection of user resources.

### `POST /admin/users`
Create a user and assign a role.

- **Request:** `name`, `email` (unique), `password` + `password_confirmation`,
  `role` (`admin`/`student`), `is_active?`.
- **Response `201`:** `{ message, data: <user resource> }`.

### `GET /admin/users/{user}`
Show one user (with roles).

- **Response `200`:** `{ data: <user resource> }`.

### `PUT/PATCH /admin/users/{user}`
Update a user (and optionally role/password).

- **Request:** any of `name`, `email` (unique, ignoring self), `password`
  (+ confirmation), `role`, `is_active`.
- **Response `200`:** `{ message, data: <user resource> }`.

### `DELETE /admin/users/{user}`
Delete a user. Deleting your own account is rejected (`422`).

- **Response `200`:** `{ message }`.

### `PATCH /admin/users/{user}/toggle-active`
Enable/disable a user. Disabling revokes all of that user's tokens. You cannot
disable your own account (`422`).

- **Response `200`:** `{ message, data: <user resource> }`.

---

## Admin — Uploads

### `POST /admin/uploads`
Admin role required. Upload an image (e.g. a question figure) to the public
disk.

- **Request:** `image` (multipart file; `jpeg/jpg/png/gif/webp`, max 4 MB).
- **Response `201`:** `{ message, data: { path, url } }`.
