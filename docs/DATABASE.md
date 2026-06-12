# UniMed — Database Schema

The backend uses a relational database (PostgreSQL in production and the local
Docker stack; SQLite in some test contexts). The schema is defined by the
migrations in `apps/backend/database/migrations`. This document covers every
domain table: purpose, key columns, relationships, and notable indexes.

All domain tables use a `bigint` auto-increment `id` primary key and Laravel
`created_at` / `updated_at` timestamps unless otherwise noted.

---

## Entity-relationship diagram

```mermaid
erDiagram
    users ||--o{ quiz_attempts : "takes"
    users ||--|| rankings : "has"
    users ||--o{ activity_logs : "performs"
    users ||--o{ questions : "authors"
    users ||--o{ quizzes : "authors"

    subjects ||--o{ questions : "categorises"

    questions ||--o{ question_answers : "has options"
    questions }o--o{ quizzes : "quiz_questions"

    quizzes ||--o{ quiz_questions : ""
    questions ||--o{ quiz_questions : ""

    quizzes ||--o{ quiz_attempts : "is attempted as"
    quiz_attempts ||--o{ quiz_attempt_answers : "records"
    questions ||--o{ quiz_attempt_answers : "answered in"

    users ||--o{ model_has_roles : "assigned"
    roles ||--o{ model_has_roles : ""
    roles }o--o{ permissions : "role_has_permissions"

    users {
        bigint id PK
        string name
        string email UK
        string password
        string avatar_path
        boolean is_active
        timestamp last_login_at
    }
    subjects {
        bigint id PK
        string name
        string slug UK
        string color
        smallint position
    }
    questions {
        bigint id PK
        bigint subject_id FK
        string type
        string difficulty
        text text
        boolean is_active
        bigint created_by FK
    }
    question_answers {
        bigint id PK
        bigint question_id FK
        text text
        boolean is_correct
        smallint position
    }
    quizzes {
        bigint id PK
        string title
        string slug UK
        smallint time_limit_minutes
        smallint question_count
        boolean is_published
        boolean is_auto_generated
        json settings
        bigint created_by FK
    }
    quiz_questions {
        bigint id PK
        bigint quiz_id FK
        bigint question_id FK
        smallint position
    }
    quiz_attempts {
        bigint id PK
        bigint user_id FK
        bigint quiz_id FK
        string status
        smallint correct_count
        decimal percentage
        int points
        int time_spent_seconds
        timestamp completed_at
    }
    quiz_attempt_answers {
        bigint id PK
        bigint quiz_attempt_id FK
        bigint question_id FK
        json selected_answer_ids
        boolean is_answered
        boolean is_correct
    }
    rankings {
        bigint id PK
        bigint user_id FK UK
        int quizzes_completed
        int total_points
        decimal average_score
        int position
    }
    activity_logs {
        bigint id PK
        bigint user_id FK
        string action
        string subject_type
        bigint subject_id
        json properties
    }
```

---

## Tables

### `users`
*Migration: `0001_01_01_000000_create_users_table.php`*

Application accounts (both students and administrators).

- **Key columns:** `name`, `email` (**unique**), `password` (hashed),
  `avatar_path` (nullable), `is_active` (boolean, default `true`),
  `last_login_at`, `remember_token`, `email_verified_at`.
- **Relationships:** `hasMany` `quiz_attempts`, `hasMany` `activity_logs`,
  `hasOne` `rankings`; authors `questions` and `quizzes` via their `created_by`.
  Roles/permissions are attached through the Spatie pivot tables.
- **Indexes:** unique `email`; index on `is_active` (used to filter disabled
  accounts).
- The migration also creates the framework `password_reset_tokens` and
  `sessions` tables.

### Roles & permissions (Spatie)
*Migration: `2026_06_12_094255_create_permission_tables.php`*

The `spatie/laravel-permission` tables:

- **`permissions`** — `name`, `guard_name` (unique together). Seeded names:
  `manage users`, `manage subjects`, `manage questions`, `manage quizzes`,
  `view admin analytics`, `take quizzes`, `view rankings`.
- **`roles`** — `name`, `guard_name`. Seeded roles: `admin`, `student`.
- **`model_has_permissions`**, **`model_has_roles`** — polymorphic pivots
  linking models (users) to permissions/roles.
- **`role_has_permissions`** — pivot linking roles to permissions.

Indexes follow the Spatie defaults (composite morph indexes + uniques).

### `personal_access_tokens` (Sanctum)
*Migration: `2026_06_12_094254_create_personal_access_tokens_table.php`*

Bearer tokens for API auth. Polymorphic `tokenable` morph, hashed `token`
(**unique**, 64 chars), `abilities`, `last_used_at`, and an indexed `expires_at`.

### `subjects`
*Migration: `2026_06_12_100000_create_subjects_table.php`*

Top-level question categories (e.g. Biology, Chemistry, Physics).

- **Key columns:** `name`, `slug` (**unique**, route key), `description`,
  `color` (hex, default `#0F5EFF`), `icon`, `position` (display order).
- **Relationships:** `hasMany` `questions`.
- **Indexes:** unique `slug`; index on `position`.

### `questions`
*Migration: `2026_06_12_100100_create_questions_table.php`*

The question bank.

- **Key columns:** `subject_id` (FK, cascade on delete), `type`
  (`single` / `multiple`, default `single`), `difficulty`
  (`easy` / `medium` / `hard`, default `medium`), `text`, `explanation`
  (nullable), `image_path` (nullable), `is_active` (boolean), `created_by`
  (FK → users, null on delete).
- **Relationships:** `belongsTo` `subject`; `hasMany` `question_answers`;
  `belongsToMany` `quizzes` through `quiz_questions`; `belongsTo` author.
- **Indexes:** single-column indexes on `difficulty`, `type`, `is_active`;
  composite indexes `(subject_id, difficulty)` and `(subject_id, is_active)`
  to back the auto-generation and admin-listing filters.

### `question_answers`
*Migration: `2026_06_12_100200_create_question_answers_table.php`*

The answer options for each question.

- **Key columns:** `question_id` (FK, cascade on delete), `text`, `is_correct`
  (boolean, default `false`), `position` (display order).
- **Relationships:** `belongsTo` `question`.
- **Indexes:** composite `(question_id, position)` (ordered fetch of a
  question's options).

### `quizzes`
*Migration: `2026_06_12_100300_create_quizzes_table.php`*

A quiz is an ordered set of questions, either hand-picked or auto-generated.

- **Key columns:** `title`, `slug` (**unique**, route key), `description`,
  `time_limit_minutes` (nullable), `question_count` (cached count),
  `difficulty` (nullable), `is_published` (boolean, default `false`),
  `is_auto_generated` (boolean), `settings` (JSON — stores the auto-generation
  filters: `subject_ids`, `difficulty`, `requested_count`), `created_by`
  (FK → users, null on delete).
- **Relationships:** `belongsToMany` `questions` (ordered by pivot position);
  `hasMany` `quiz_attempts`; `belongsTo` author.
- **Indexes:** unique `slug`; index on `is_published`; composite
  `(is_published, created_at)` for the published catalogue listing.

### `quiz_questions`
*Migration: `2026_06_12_100400_create_quiz_questions_table.php`*

The many-to-many pivot joining quizzes and questions, with ordering.

- **Key columns:** `quiz_id` (FK, cascade), `question_id` (FK, cascade),
  `position` (order within the quiz).
- **Indexes:** **unique** `(quiz_id, question_id)` (a question appears at most
  once per quiz); composite `(quiz_id, position)` for ordered retrieval.

### `quiz_attempts`
*Migration: `2026_06_12_100500_create_quiz_attempts_table.php`*

A single student's run at a quiz, including the denormalised scored result.

- **Key columns:** `user_id` (FK, cascade), `quiz_id` (FK, cascade), `status`
  (`in_progress` / `completed` / `abandoned`, default `in_progress`),
  `total_questions`, `correct_count`, `incorrect_count`, `unanswered_count`,
  `percentage` (decimal 5,2), `points` (unsigned int), `time_spent_seconds`
  (nullable), `started_at`, `completed_at`.
- **Relationships:** `belongsTo` `user`; `belongsTo` `quiz`; `hasMany`
  `quiz_attempt_answers`.
- **Indexes:** index on `status`; composites `(user_id, status)` and
  `(quiz_id, status)`; index on `completed_at` (powers trends/recent-activity
  analytics).

### `quiz_attempt_answers`
*Migration: `2026_06_12_100600_create_quiz_attempt_answers_table.php`*

One row per question per attempt — created up front as placeholders, then
updated in place by autosave and finalised at submission.

- **Key columns:** `quiz_attempt_id` (FK, cascade), `question_id` (FK,
  cascade), `selected_answer_ids` (JSON array, nullable), `is_answered`
  (boolean), `is_correct` (boolean, **nullable** — only set after scoring),
  `answered_at` (nullable).
- **Relationships:** `belongsTo` `quiz_attempt`; `belongsTo` `question`.
- **Indexes:** **unique** `(quiz_attempt_id, question_id)` (one answer per
  question per attempt — enables in-place upsert semantics); index on
  `question_id` (used by per-subject accuracy joins).

### `rankings`
*Migration: `2026_06_12_100700_create_rankings_table.php`*

A denormalised, one-row-per-student leaderboard, recomputed on each submission.

- **Key columns:** `user_id` (FK, **unique**, cascade), `quizzes_completed`,
  `total_points`, `average_score` (decimal 5,2), `best_score` (decimal 5,2),
  `average_time_seconds`, `position` (nullable leaderboard rank),
  `last_activity_at`.
- **Relationships:** `belongsTo` `user` (one-to-one).
- **Indexes:** unique `user_id`; index on `total_points` (primary ranking sort)
  and on `position` (ordered leaderboard reads).

### `activity_logs`
*Migration: `2026_06_12_100800_create_activity_logs_table.php`*

An append-only audit trail for analytics and security.

- **Key columns:** `user_id` (FK, null on delete), `action`, `description`
  (nullable), `subject_type` + `subject_id` (polymorphic subject), `properties`
  (JSON), `ip_address`. Only `created_at` is kept (no `updated_at`).
- **Relationships:** `belongsTo` `user`.
- **Indexes:** index on `action`; composite `(subject_type, subject_id)`
  (look up a subject's history) and `(user_id, created_at)` (a user's timeline).

---

## Indexing & read-optimisation notes

The schema favours **fast reads on the student- and admin-facing hot paths**:

- **Denormalised aggregates.** `quiz_attempts` stores the fully-computed score
  (counts, percentage, points, time) at submission, and `rankings` stores the
  per-student leaderboard totals. Dashboards and the leaderboard therefore read
  pre-aggregated rows rather than recomputing across `quiz_attempt_answers` on
  every request.
- **Cached counters.** `quizzes.question_count` (and `subjects` active-question
  counts via `withCount`) avoid pivot/joins for list views.
- **Composite indexes match the actual query shapes.** Filters like
  "published quizzes ordered by recency" `(is_published, created_at)`,
  "this user's attempts by status" `(user_id, status)`, and
  "questions in a subject of a difficulty" `(subject_id, difficulty)` each
  have a backing composite index.
- **Uniqueness as a correctness guarantee.** Unique pivots on
  `(quiz_id, question_id)` and `(quiz_attempt_id, question_id)`, and unique
  `rankings.user_id`, enforce the one-row invariants that the in-place
  autosave/upsert and `updateOrCreate` logic rely on.
- **Cross-database portability.** Where raw aggregate SQL needs a boolean
  literal, `AnalyticsService` emits `true`/`1` per driver so the same queries
  run on PostgreSQL and SQLite.
