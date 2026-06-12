<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * The five core admission-exam subjects, matching the dashboard charts.
     */
    public const SUBJECTS = [
        ['name' => 'Biology', 'slug' => 'biology', 'color' => '#16A34A', 'icon' => 'dna', 'position' => 1, 'description' => 'Cell biology, genetics, human physiology, biochemistry and evolution.'],
        ['name' => 'Chemistry', 'slug' => 'chemistry', 'color' => '#9333EA', 'icon' => 'flask-conical', 'position' => 2, 'description' => 'Atomic structure, bonding, stoichiometry, acids and bases, organic chemistry.'],
        ['name' => 'Physics', 'slug' => 'physics', 'color' => '#0891B2', 'icon' => 'atom', 'position' => 3, 'description' => 'Mechanics, energy, thermodynamics, electricity, optics and measurement.'],
        ['name' => 'Mathematics', 'slug' => 'mathematics', 'color' => '#EA580C', 'icon' => 'sigma', 'position' => 4, 'description' => 'Algebra, functions, geometry, probability and quantitative reasoning.'],
        ['name' => 'Logic', 'slug' => 'logic', 'color' => '#0F5EFF', 'icon' => 'brain', 'position' => 5, 'description' => 'Logical deduction, numerical series, syllogisms and problem solving.'],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $subject) {
            Subject::updateOrCreate(['slug' => $subject['slug']], $subject);
        }
    }
}
