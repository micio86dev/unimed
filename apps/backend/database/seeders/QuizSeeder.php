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
            ['title' => 'Medicine Admission — Full Simulation', 'title_it' => 'Ammissione Medicina — Simulazione Completa', 'desc' => 'A complete mock exam covering every subject, timed like the real thing.', 'desc_it' => 'Una simulazione completa che copre tutte le materie, cronometrata come la prova reale.', 'time' => 100, 'count' => 60, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Healthcare Professions — Mock Exam', 'title_it' => 'Professioni Sanitarie — Simulazione d\'Esame', 'desc' => 'Full simulation tailored to healthcare profession admissions.', 'desc_it' => 'Simulazione completa pensata per le ammissioni alle professioni sanitarie.', 'time' => 90, 'count' => 50, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Veterinary Medicine — Practice Test', 'title_it' => 'Medicina Veterinaria — Test di Esercitazione', 'desc' => 'Mixed-subject practice test for veterinary admissions.', 'desc_it' => 'Test di esercitazione a materie miste per le ammissioni a Veterinaria.', 'time' => 80, 'count' => 45, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Biology Fundamentals', 'title_it' => 'Fondamenti di Biologia', 'desc' => 'Core biology concepts every candidate must master.', 'desc_it' => 'I concetti fondamentali di biologia che ogni candidato deve padroneggiare.', 'time' => 30, 'count' => 25, 'subjects' => ['biology'], 'difficulty' => null],
            ['title' => 'Biology — Advanced Drill', 'title_it' => 'Biologia — Esercitazione Avanzata', 'desc' => 'Challenging biology questions for high scorers.', 'desc_it' => 'Domande di biologia impegnative per i candidati più preparati.', 'time' => 35, 'count' => 25, 'subjects' => ['biology'], 'difficulty' => 'hard'],
            ['title' => 'Chemistry Essentials', 'title_it' => 'Elementi di Chimica', 'desc' => 'Atomic structure, bonding and reactions.', 'desc_it' => 'Struttura atomica, legami chimici e reazioni.', 'time' => 30, 'count' => 25, 'subjects' => ['chemistry'], 'difficulty' => null],
            ['title' => 'Chemistry — Quick Warm-up', 'title_it' => 'Chimica — Riscaldamento Rapido', 'desc' => 'A short, easy set to build confidence.', 'desc_it' => 'Un set breve e semplice per prendere confidenza.', 'time' => 15, 'count' => 15, 'subjects' => ['chemistry'], 'difficulty' => 'easy'],
            ['title' => 'Physics in Practice', 'title_it' => 'Fisica in Pratica', 'desc' => 'Mechanics, energy and electricity problems.', 'desc_it' => 'Problemi di meccanica, energia ed elettricità.', 'time' => 30, 'count' => 25, 'subjects' => ['physics'], 'difficulty' => null],
            ['title' => 'Physics — Mechanics Focus', 'title_it' => 'Fisica — Focus sulla Meccanica', 'desc' => 'Targeted practice on motion and forces.', 'desc_it' => 'Esercitazione mirata su moto e forze.', 'time' => 25, 'count' => 20, 'subjects' => ['physics'], 'difficulty' => 'medium'],
            ['title' => 'Mathematics Refresher', 'title_it' => 'Ripasso di Matematica', 'desc' => 'Algebra, percentages and quantitative reasoning.', 'desc_it' => 'Algebra, percentuali e ragionamento quantitativo.', 'time' => 30, 'count' => 25, 'subjects' => ['mathematics'], 'difficulty' => null],
            ['title' => 'Mathematics — Speed Round', 'title_it' => 'Matematica — Round Veloce', 'desc' => 'Fast-paced arithmetic and algebra.', 'desc_it' => 'Aritmetica e algebra a ritmo serrato.', 'time' => 15, 'count' => 20, 'subjects' => ['mathematics'], 'difficulty' => 'easy'],
            ['title' => 'Logic & Reasoning', 'title_it' => 'Logica e Ragionamento', 'desc' => 'Number series, deduction and problem solving.', 'desc_it' => 'Serie numeriche, deduzione e problem solving.', 'time' => 25, 'count' => 25, 'subjects' => ['logic'], 'difficulty' => null],
            ['title' => 'Logic — Number Series', 'title_it' => 'Logica — Serie Numeriche', 'desc' => 'Pattern recognition drills.', 'desc_it' => 'Esercizi di riconoscimento di schemi.', 'time' => 20, 'count' => 20, 'subjects' => ['logic'], 'difficulty' => null],
            ['title' => 'Science Combo — Bio & Chem', 'title_it' => 'Combo Scientifico — Bio e Chimica', 'desc' => 'Combined biology and chemistry assessment.', 'desc_it' => 'Valutazione combinata di biologia e chimica.', 'time' => 40, 'count' => 30, 'subjects' => ['biology', 'chemistry'], 'difficulty' => null],
            ['title' => 'Science Combo — Physics & Maths', 'title_it' => 'Combo Scientifico — Fisica e Matematica', 'desc' => 'Quantitative subjects in one test.', 'desc_it' => 'Le materie quantitative in un unico test.', 'time' => 40, 'count' => 30, 'subjects' => ['physics', 'mathematics'], 'difficulty' => null],
            ['title' => 'Daily Warm-up', 'title_it' => 'Riscaldamento Quotidiano', 'desc' => 'A short mixed set to keep your streak alive.', 'desc_it' => 'Un breve set misto per non perdere il ritmo.', 'time' => 10, 'count' => 10, 'subjects' => null, 'difficulty' => 'easy'],
            ['title' => 'Weekend Challenge', 'title_it' => 'Sfida del Weekend', 'desc' => 'Hard mixed questions for serious candidates.', 'desc_it' => 'Domande miste difficili per candidati determinati.', 'time' => 45, 'count' => 30, 'subjects' => null, 'difficulty' => 'hard'],
            ['title' => 'Mid-difficulty Mixed Set', 'title_it' => 'Set Misto di Media Difficoltà', 'desc' => 'Balanced mixed-subject practice.', 'desc_it' => 'Esercitazione equilibrata a materie miste.', 'time' => 35, 'count' => 30, 'subjects' => null, 'difficulty' => 'medium'],
            ['title' => 'Rapid Review — All Subjects', 'title_it' => 'Ripasso Rapido — Tutte le Materie', 'desc' => 'Bite-sized refresher across every topic.', 'desc_it' => 'Un ripasso veloce su tutti gli argomenti.', 'time' => 20, 'count' => 20, 'subjects' => null, 'difficulty' => null],
            ['title' => 'Final Countdown Simulation', 'title_it' => 'Simulazione Conto alla Rovescia Finale', 'desc' => 'Last-week full mock under exam conditions.', 'desc_it' => 'Simulazione completa dell\'ultima settimana in condizioni d\'esame.', 'time' => 100, 'count' => 60, 'subjects' => null, 'difficulty' => null],
        ];

        foreach ($specs as $index => $spec) {
            $subjectIds = $spec['subjects'] === null
                ? []
                : array_values(array_filter(array_map(fn ($slug) => $subjects[$slug] ?? null, $spec['subjects'])));

            $this->quizzes->generate([
                'title' => $spec['title'],
                'title_it' => $spec['title_it'],
                'description' => $spec['desc'],
                'description_it' => $spec['desc_it'],
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
