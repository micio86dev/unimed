<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Subject;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function __construct(private readonly QuizService $quizzes) {}

    public function run(): void
    {
        $author = User::where('email', 'admin@unimed.app')->first();
        $subjects = Subject::pluck('id', 'slug')->all();

        $specs = [
            ['title' => 'Medicine Admission — Full Simulation', 'desc' => 'A complete mock exam covering every subject, timed like the real thing.', 'time' => 100, 'count' => 60, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Healthcare Professions — Mock Exam', 'desc' => 'Full simulation tailored to healthcare profession admissions.', 'time' => 90, 'count' => 50, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Veterinary Medicine — Practice Test', 'desc' => 'Mixed-subject practice test for veterinary admissions.', 'time' => 80, 'count' => 45, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Biology Fundamentals', 'desc' => 'Core biology concepts every candidate must master.', 'time' => 30, 'count' => 25, 'subjects' => ['biology'], 'difficulty' => null],
            ['title' => 'Biology — Advanced Drill', 'desc' => 'Challenging biology questions for high scorers.', 'time' => 35, 'count' => 25, 'subjects' => ['biology'], 'difficulty' => 'hard'],
            ['title' => 'Chemistry Essentials', 'desc' => 'Atomic structure, bonding and reactions.', 'time' => 30, 'count' => 25, 'subjects' => ['chemistry'], 'difficulty' => null],
            ['title' => 'Chemistry — Quick Warm-up', 'desc' => 'A short, easy set to build confidence.', 'time' => 15, 'count' => 15, 'subjects' => ['chemistry'], 'difficulty' => 'easy'],
            ['title' => 'Physics in Practice', 'desc' => 'Mechanics, energy and electricity problems.', 'time' => 30, 'count' => 25, 'subjects' => ['physics'], 'difficulty' => null],
            ['title' => 'Physics — Mechanics Focus', 'desc' => 'Targeted practice on motion and forces.', 'time' => 25, 'count' => 20, 'subjects' => ['physics'], 'difficulty' => 'medium'],
            ['title' => 'Mathematics Refresher', 'desc' => 'Algebra, percentages and quantitative reasoning.', 'time' => 30, 'count' => 25, 'subjects' => ['mathematics'], 'difficulty' => null],
            ['title' => 'Mathematics — Speed Round', 'desc' => 'Fast-paced arithmetic and algebra.', 'time' => 15, 'count' => 20, 'subjects' => ['mathematics'], 'difficulty' => 'easy'],
            ['title' => 'Logic & Reasoning', 'desc' => 'Number series, deduction and problem solving.', 'time' => 25, 'count' => 25, 'subjects' => ['logic'], 'difficulty' => null],
            ['title' => 'Logic — Number Series', 'desc' => 'Pattern recognition drills.', 'time' => 20, 'count' => 20, 'subjects' => ['logic'], 'difficulty' => null],
            ['title' => 'Science Combo — Bio & Chem', 'desc' => 'Combined biology and chemistry assessment.', 'time' => 40, 'count' => 30, 'subjects' => ['biology', 'chemistry'], 'difficulty' => null],
            ['title' => 'Science Combo — Physics & Maths', 'desc' => 'Quantitative subjects in one test.', 'time' => 40, 'count' => 30, 'subjects' => ['physics', 'mathematics'], 'difficulty' => null],
            ['title' => 'Daily Warm-up', 'desc' => 'A short mixed set to keep your streak alive.', 'time' => 10, 'count' => 10, 'subjects' => null, 'difficulty' => 'easy'],
            ['title' => 'Weekend Challenge', 'desc' => 'Hard mixed questions for serious candidates.', 'time' => 45, 'count' => 30, 'subjects' => null, 'difficulty' => 'hard'],
            ['title' => 'Mid-difficulty Mixed Set', 'desc' => 'Balanced mixed-subject practice.', 'time' => 35, 'count' => 30, 'subjects' => null, 'difficulty' => 'medium'],
            ['title' => 'Rapid Review — All Subjects', 'desc' => 'Bite-sized refresher across every topic.', 'time' => 20, 'count' => 20, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Final Countdown Simulation', 'desc' => 'Last-week full mock under exam conditions.', 'time' => 100, 'count' => 60, 'subjects' => null, 'difficulty' => null],
        ];

        foreach ($specs as $index => $spec) {
            $subjectIds = $spec['subjects'] === null
                ? []
                : array_values(array_filter(array_map(fn ($slug) => $subjects[$slug] ?? null, $spec['subjects'])));

            $this->quizzes->generate([
                'title' => $spec['title'],
                'description' => $spec['desc'],
                'time_limit_minutes' => $spec['time'],
                'question_count' => $spec['count'],
                'subject_ids' => $subjectIds,
                'difficulty' => $spec['difficulty'],
                // Leave the last two as drafts to demo the admin publish workflow.
                'is_published' => $index < count($specs) - 2,
            ], $author);
        }
    }
}
