<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Enums\UserRole;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-optimised aggregation for the student and admin analytics dashboards.
 */
final class AnalyticsService
{
    /**
     * KPI + chart payload for a single student.
     *
     * @return array<string, mixed>
     */
    public function forStudent(User $user): array
    {
        $completed = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value);

        $kpis = (clone $completed)
            ->selectRaw('COUNT(*) as completed_quizzes')
            ->selectRaw('COALESCE(AVG(percentage), 0) as average_score')
            ->selectRaw('COALESCE(MAX(percentage), 0) as best_score')
            ->selectRaw('COALESCE(AVG(time_spent_seconds), 0) as average_time')
            ->selectRaw('COALESCE(SUM(points), 0) as total_points')
            ->first();

        return [
            'kpis' => [
                'completed_quizzes' => (int) $kpis->completed_quizzes,
                'average_score' => round((float) $kpis->average_score, 1),
                'best_score' => round((float) $kpis->best_score, 1),
                'average_time_seconds' => (int) round((float) $kpis->average_time),
                'total_points' => (int) $kpis->total_points,
            ],
            'subject_performance' => $this->studentSubjectPerformance($user),
            'recent_attempts' => $this->studentRecentAttempts($user),
            'score_trend' => $this->studentScoreTrend($user),
        ];
    }

    /**
     * Per-subject accuracy for a student across all completed attempts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function studentSubjectPerformance(User $user): array
    {
        $rows = DB::table('quiz_attempt_answers as qaa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'qaa.quiz_attempt_id')
            ->join('questions as q', 'q.id', '=', 'qaa.question_id')
            ->join('subjects as s', 's.id', '=', 'q.subject_id')
            ->where('qa.user_id', $user->id)
            ->where('qa.status', AttemptStatus::Completed->value)
            ->groupBy('s.id', 's.name', 's.slug', 's.color')
            ->selectRaw('s.id, s.name, s.slug, s.color')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN qaa.is_correct = '.$this->trueLiteral().' THEN 1 ELSE 0 END) as correct')
            ->get();

        return Subject::query()->orderBy('position')->get()->map(function (Subject $subject) use ($rows): array {
            $row = $rows->firstWhere('id', $subject->id);
            $total = (int) ($row->total ?? 0);
            $correct = (int) ($row->correct ?? 0);

            return [
                'subject' => $subject->name,
                'slug' => $subject->slug,
                'color' => $subject->color,
                'total' => $total,
                'correct' => $correct,
                'accuracy' => $total > 0 ? round(($correct / $total) * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function studentRecentAttempts(User $user, int $limit = 5): array
    {
        return QuizAttempt::query()
            ->with('quiz:id,title,slug')
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value)
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->map(static fn (QuizAttempt $a): array => [
                'id' => $a->id,
                'quiz' => $a->quiz?->title,
                'quiz_slug' => $a->quiz?->slug,
                'percentage' => (float) $a->percentage,
                'correct_count' => $a->correct_count,
                'total_questions' => $a->total_questions,
                'completed_at' => $a->completed_at?->toIso8601String(),
            ])->all();
    }

    /**
     * Last N completed attempts as a chronological score series.
     *
     * @return array<int, array<string, mixed>>
     */
    public function studentScoreTrend(User $user, int $limit = 10): array
    {
        return QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed->value)
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(static fn (QuizAttempt $a): array => [
                'date' => $a->completed_at?->toDateString(),
                'percentage' => (float) $a->percentage,
            ])->all();
    }

    /**
     * KPI + chart payload for the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function forAdmin(): array
    {
        $completedAttempts = QuizAttempt::query()->where('status', AttemptStatus::Completed->value);

        $activeWindow = Carbon::now()->subDays(30);
        $activeUsers = QuizAttempt::query()
            ->where('status', AttemptStatus::Completed->value)
            ->where('completed_at', '>=', $activeWindow)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'kpis' => [
                'total_users' => User::count(),
                'total_students' => User::role(UserRole::Student->value)->count(),
                'active_users' => $activeUsers,
                'completed_quizzes' => (clone $completedAttempts)->count(),
                'average_score' => round((float) (clone $completedAttempts)->avg('percentage'), 1),
                'total_questions' => Question::count(),
                'total_quizzes' => Quiz::count(),
            ],
            'hardest_subjects' => $this->hardestSubjects(),
            'attempts_trend' => $this->attemptsTrend(),
        ];
    }

    /**
     * Subjects ordered by lowest average accuracy (hardest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function hardestSubjects(): array
    {
        $rows = DB::table('quiz_attempt_answers as qaa')
            ->join('quiz_attempts as qa', 'qa.id', '=', 'qaa.quiz_attempt_id')
            ->join('questions as q', 'q.id', '=', 'qaa.question_id')
            ->join('subjects as s', 's.id', '=', 'q.subject_id')
            ->where('qa.status', AttemptStatus::Completed->value)
            ->where('qaa.is_answered', $this->trueLiteral())
            ->groupBy('s.id', 's.name', 's.slug', 's.color')
            ->selectRaw('s.name, s.slug, s.color')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN qaa.is_correct = '.$this->trueLiteral().' THEN 1 ELSE 0 END) as correct')
            ->get();

        return $rows->map(static function ($row): array {
            $total = (int) $row->total;
            $correct = (int) $row->correct;

            return [
                'subject' => $row->name,
                'slug' => $row->slug,
                'color' => $row->color,
                'accuracy' => $total > 0 ? round(($correct / $total) * 100, 1) : 0.0,
                'answered' => $total,
            ];
        })->sortBy('accuracy')->values()->all();
    }

    /**
     * Completed attempts per day for the last 14 days.
     *
     * @return array<int, array<string, mixed>>
     */
    public function attemptsTrend(int $days = 14): array
    {
        $since = Carbon::now()->startOfDay()->subDays($days - 1);

        $counts = QuizAttempt::query()
            ->where('status', AttemptStatus::Completed->value)
            ->where('completed_at', '>=', $since)
            ->get(['completed_at'])
            ->groupBy(static fn (QuizAttempt $a): string => $a->completed_at->toDateString())
            ->map->count();

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $series[] = ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
        }

        return $series;
    }

    /**
     * Boolean true literal portable across SQLite (1) and PostgreSQL (true).
     */
    private function trueLiteral(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'true' : '1';
    }
}
