<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionAnswer>
 */
class QuestionAnswerFactory extends Factory
{
    protected $model = QuestionAnswer::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text' => fake()->words(3, true),
            'is_correct' => false,
            'position' => 0,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
