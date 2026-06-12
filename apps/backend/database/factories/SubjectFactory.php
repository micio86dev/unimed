<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $base = fake()->randomElement([
            'Biology', 'Chemistry', 'Physics', 'Mathematics', 'Logic', 'Anatomy', 'Genetics',
        ]);
        $name = $base.' '.fake()->unique()->numberBetween(1, 1_000_000);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(['#0F5EFF', '#16A34A', '#9333EA', '#EA580C', '#0891B2']),
            'icon' => null,
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
