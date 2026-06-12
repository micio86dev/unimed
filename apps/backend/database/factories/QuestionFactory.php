<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'type' => QuestionType::Single,
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'text' => rtrim(fake()->sentence(), '.').'?',
            'explanation' => fake()->sentence(),
            'image_path' => null,
            'is_active' => true,
            'created_by' => null,
        ];
    }

    /**
     * Ensure every factory-built question has valid answers (exactly one
     * correct option for single choice), so it can be scored out of the box.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Question $question): void {
            if ($question->answers()->count() > 0) {
                return;
            }

            $optionCount = 4;
            $correctIndex = fake()->numberBetween(0, $optionCount - 1);

            for ($i = 0; $i < $optionCount; $i++) {
                QuestionAnswer::factory()->create([
                    'question_id' => $question->id,
                    'position' => $i,
                    'is_correct' => $i === $correctIndex,
                ]);
            }
        });
    }

    public function easy(): static
    {
        return $this->state(['difficulty' => Difficulty::Easy]);
    }

    public function medium(): static
    {
        return $this->state(['difficulty' => Difficulty::Medium]);
    }

    public function hard(): static
    {
        return $this->state(['difficulty' => Difficulty::Hard]);
    }

    public function multiple(): static
    {
        return $this->state(['type' => QuestionType::Multiple]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
