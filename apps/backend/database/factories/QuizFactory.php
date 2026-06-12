<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        $title = ucfirst(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(),
            'time_limit_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'question_count' => 0,
            'difficulty' => fake()->randomElement([null, ...Difficulty::values()]),
            'is_published' => true,
            'is_auto_generated' => false,
            'settings' => null,
            'created_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
