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
        ['name' => 'Biology', 'name_it' => 'Biologia', 'slug' => 'biology', 'color' => '#16A34A', 'icon' => 'dna', 'position' => 1, 'description' => 'Cell biology, genetics, human physiology, biochemistry and evolution.', 'description_it' => 'Biologia cellulare, genetica, fisiologia umana, biochimica ed evoluzione.'],
        ['name' => 'Chemistry', 'name_it' => 'Chimica', 'slug' => 'chemistry', 'color' => '#9333EA', 'icon' => 'flask-conical', 'position' => 2, 'description' => 'Atomic structure, bonding, stoichiometry, acids and bases, organic chemistry.', 'description_it' => 'Struttura atomica, legami, stechiometria, acidi e basi, chimica organica.'],
        ['name' => 'Physics', 'name_it' => 'Fisica', 'slug' => 'physics', 'color' => '#0891B2', 'icon' => 'atom', 'position' => 3, 'description' => 'Mechanics, energy, thermodynamics, electricity, optics and measurement.', 'description_it' => 'Meccanica, energia, termodinamica, elettricità, ottica e misure.'],
        ['name' => 'Mathematics', 'name_it' => 'Matematica', 'slug' => 'mathematics', 'color' => '#EA580C', 'icon' => 'sigma', 'position' => 4, 'description' => 'Algebra, functions, geometry, probability and quantitative reasoning.', 'description_it' => 'Algebra, funzioni, geometria, probabilità e ragionamento quantitativo.'],
        ['name' => 'Logic', 'name_it' => 'Logica', 'slug' => 'logic', 'color' => '#0F5EFF', 'icon' => 'brain', 'position' => 5, 'description' => 'Logical deduction, numerical series, syllogisms and problem solving.', 'description_it' => 'Deduzione logica, serie numeriche, sillogismi e problem solving.'],
    ];

    public function run(): void
    {
        foreach (self::SUBJECTS as $subject) {
            Subject::updateOrCreate(['slug' => $subject['slug']], $subject);
        }
    }
}
