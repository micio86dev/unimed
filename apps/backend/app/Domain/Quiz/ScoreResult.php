<?php

declare(strict_types=1);

namespace App\Domain\Quiz;

/**
 * Immutable result of scoring a quiz attempt. Pure data — no persistence.
 */
final class ScoreResult
{
    /**
     * @param  array<int, QuestionOutcome>  $outcomes  keyed by question id
     */
    public function __construct(
        public readonly int $totalQuestions,
        public readonly int $correctCount,
        public readonly int $incorrectCount,
        public readonly int $unansweredCount,
        public readonly float $percentage,
        public readonly int $points,
        public readonly array $outcomes,
    ) {}
}
