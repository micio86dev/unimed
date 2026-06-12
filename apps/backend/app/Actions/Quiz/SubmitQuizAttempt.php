<?php

declare(strict_types=1);

namespace App\Actions\Quiz;

use App\Domain\Quiz\QuizScoringService;
use App\Enums\AttemptStatus;
use App\Models\QuizAttempt;
use App\Services\RankingService;
use Illuminate\Support\Facades\DB;

final class SubmitQuizAttempt
{
    public function __construct(
        private readonly QuizScoringService $scorer,
        private readonly RankingService $rankings,
    ) {}

    /**
     * Finalise an in-progress attempt: score it, persist per-question
     * correctness and aggregate stats, then refresh the student's ranking.
     */
    public function handle(QuizAttempt $attempt, ?int $timeSpentSeconds = null): QuizAttempt
    {
        $attempt->loadMissing(['answers', 'quiz.questions.answers']);

        $questions = $attempt->quiz->questions;

        $selections = $attempt->answers
            ->mapWithKeys(static fn ($answer): array => [$answer->question_id => $answer->selected_answer_ids])
            ->all();

        $result = $this->scorer->score($questions, $selections);

        $elapsed = $timeSpentSeconds ?? ($attempt->started_at !== null
            ? max(0, now()->diffInSeconds($attempt->started_at, absolute: true))
            : null);

        return DB::transaction(function () use ($attempt, $result, $elapsed): QuizAttempt {
            foreach ($attempt->answers as $answer) {
                $outcome = $result->outcomes[$answer->question_id] ?? null;

                if ($outcome === null) {
                    continue;
                }

                $answer->update([
                    'is_correct' => $outcome->isAnswered ? $outcome->isCorrect : null,
                ]);
            }

            $attempt->update([
                'status' => AttemptStatus::Completed,
                'total_questions' => $result->totalQuestions,
                'correct_count' => $result->correctCount,
                'incorrect_count' => $result->incorrectCount,
                'unanswered_count' => $result->unansweredCount,
                'percentage' => $result->percentage,
                'points' => $result->points,
                'time_spent_seconds' => $elapsed,
                'completed_at' => now(),
            ]);

            $this->rankings->recalculateFor($attempt->user_id);

            return $attempt->fresh(['answers', 'quiz']);
        });
    }
}
