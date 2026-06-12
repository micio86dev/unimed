<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'status' => AttemptStatus::InProgress,
            'total_questions' => 0,
            'correct_count' => 0,
            'incorrect_count' => 0,
            'unanswered_count' => 0,
            'percentage' => 0,
            'points' => 0,
            'time_spent_seconds' => null,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (): array {
            $total = fake()->numberBetween(10, 30);
            $correct = fake()->numberBetween(0, $total);
            $incorrect = $total - $correct;

            return [
                'status' => AttemptStatus::Completed,
                'total_questions' => $total,
                'correct_count' => $correct,
                'incorrect_count' => $incorrect,
                'unanswered_count' => 0,
                'percentage' => round(($correct / $total) * 100, 2),
                'points' => $correct * 20,
                'time_spent_seconds' => fake()->numberBetween(120, 3600),
                'started_at' => now()->subMinutes(fake()->numberBetween(10, 60)),
                'completed_at' => now()->subMinutes(fake()->numberBetween(0, 5)),
            ];
        });
    }
}
