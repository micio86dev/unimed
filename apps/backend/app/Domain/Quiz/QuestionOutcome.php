<?php

declare(strict_types=1);

namespace App\Domain\Quiz;

final class QuestionOutcome
{
    public function __construct(
        public readonly int $questionId,
        public readonly bool $isAnswered,
        public readonly bool $isCorrect,
        public readonly int $pointsAwarded,
    ) {}
}
