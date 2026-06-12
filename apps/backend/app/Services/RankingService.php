<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use App\Models\Ranking;
use Illuminate\Support\Facades\DB;

/**
 * Maintains the denormalised `rankings` leaderboard table. Aggregates are
 * recomputed from a student's completed attempts whenever they finish a quiz.
 */
final class RankingService
{
    /**
     * Recompute a single student's ranking aggregates, then refresh positions.
     */
    public function recalculateFor(int $userId): void
    {
        $stats = QuizAttempt::query()
            ->where('user_id', $userId)
            ->where('status', AttemptStatus::Completed->value)
            ->selectRaw('COUNT(*) as completed')
            ->selectRaw('COALESCE(SUM(points), 0) as total_points')
            ->selectRaw('COALESCE(AVG(percentage), 0) as average_score')
            ->selectRaw('COALESCE(MAX(percentage), 0) as best_score')
            ->selectRaw('COALESCE(AVG(time_spent_seconds), 0) as average_time')
            ->selectRaw('MAX(completed_at) as last_activity')
            ->first();

        Ranking::updateOrCreate(
            ['user_id' => $userId],
            [
                'quizzes_completed' => (int) ($stats->completed ?? 0),
                'total_points' => (int) ($stats->total_points ?? 0),
                'average_score' => round((float) ($stats->average_score ?? 0), 2),
                'best_score' => round((float) ($stats->best_score ?? 0), 2),
                'average_time_seconds' => (int) round((float) ($stats->average_time ?? 0)),
                'last_activity_at' => $stats->last_activity ?? null,
            ],
        );

        $this->recalculatePositions();
    }

    /**
     * Assign leaderboard positions ordered by total points, then average score.
     */
    public function recalculatePositions(): void
    {
        $rankings = Ranking::query()
            ->orderByDesc('total_points')
            ->orderByDesc('average_score')
            ->orderBy('average_time_seconds')
            ->get();

        DB::transaction(function () use ($rankings): void {
            $position = 0;
            foreach ($rankings as $ranking) {
                $position++;
                if ($ranking->position !== $position) {
                    $ranking->update(['position' => $position]);
                }
            }
        });
    }

    /**
     * Recompute every student's ranking (used by seeders / maintenance).
     */
    public function recalculateAll(): void
    {
        $userIds = QuizAttempt::query()
            ->where('status', AttemptStatus::Completed->value)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            $this->recalculateFor((int) $userId);
        }
    }
}
