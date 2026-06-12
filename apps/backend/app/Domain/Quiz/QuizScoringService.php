<?php

declare(strict_types=1);

namespace App\Domain\Quiz;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Pure scoring engine for quiz attempts.
 *
 * A question is correct when the set of selected answer ids is EXACTLY equal to
 * the set of correct answer ids (order-independent). This holds for both single
 * and multiple choice questions: partial selections never count as correct.
 *
 * Points: each correct question awards 10 × its difficulty weight
 * (easy = 10, medium = 20, hard = 30).
 */
final class QuizScoringService
{
    public const POINTS_PER_WEIGHT = 10;

    /**
     * @param  Collection<int, Question>  $questions  questions with `answers` loaded
     * @param  array<int, array<int, int>|null>  $selectionsByQuestion  question id => selected answer ids
     */
    public function score(Collection $questions, array $selectionsByQuestion): ScoreResult
    {
        $total = $questions->count();
        $correct = 0;
        $incorrect = 0;
        $unanswered = 0;
        $points = 0;
        $outcomes = [];

        foreach ($questions as $question) {
            $selected = $this->normalise($selectionsByQuestion[$question->id] ?? null);
            $expected = $question->correctAnswerIds();

            $isAnswered = $selected !== [];

            if (! $isAnswered) {
                $unanswered++;
                $outcomes[$question->id] = new QuestionOutcome($question->id, false, false, 0);

                continue;
            }

            $isCorrect = $selected === $expected;
            $awarded = 0;

            if ($isCorrect) {
                $correct++;
                $awarded = self::POINTS_PER_WEIGHT * $question->difficulty->weight();
                $points += $awarded;
            } else {
                $incorrect++;
            }

            $outcomes[$question->id] = new QuestionOutcome($question->id, true, $isCorrect, $awarded);
        }

        $percentage = $total > 0 ? round(($correct / $total) * 100, 2) : 0.0;

        return new ScoreResult(
            totalQuestions: $total,
            correctCount: $correct,
            incorrectCount: $incorrect,
            unansweredCount: $unanswered,
            percentage: $percentage,
            points: $points,
            outcomes: $outcomes,
        );
    }

    /**
     * @param  array<int, int|string>|null  $ids
     * @return array<int, int>
     */
    private function normalise(?array $ids): array
    {
        if ($ids === null) {
            return [];
        }

        return collect($ids)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
