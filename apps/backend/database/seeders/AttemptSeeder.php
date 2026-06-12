<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Quiz\QuizScoringService;
use App\Enums\AttemptStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttemptSeeder extends Seeder
{
    public function __construct(
        private readonly QuizScoringService $scorer,
        private readonly RankingService $rankings,
    ) {}

    public function run(): void
    {
        $students = User::role(UserRole::Student->value)->get();
        $quizzes = Quiz::published()->with('questions.answers')->get()->filter(fn (Quiz $q) => $q->questions->isNotEmpty());

        if ($quizzes->isEmpty()) {
            return;
        }

        foreach ($students as $student) {
            // Each student has an innate "skill" that shapes their scores.
            $skill = mt_rand(45, 95) / 100;
            $attemptCount = $student->is_active ? random_int(1, 8) : random_int(0, 2);

            $chosen = $quizzes->random(min($attemptCount, $quizzes->count()));
            $chosen = $chosen instanceof Quiz ? collect([$chosen]) : $chosen;

            foreach ($chosen as $quiz) {
                $this->seedAttempt($student, $quiz, $skill);
            }
        }

        $this->rankings->recalculateAll();
    }

    private function seedAttempt(User $student, Quiz $quiz, float $skill): void
    {
        $questions = $quiz->questions;

        $completedAt = Carbon::now()
            ->subDays(random_int(0, 44))
            ->subMinutes(random_int(0, 1439));
        $timeSpent = random_int(90, max(180, ($quiz->time_limit_minutes ?? 30) * 60));
        $startedAt = $completedAt->copy()->subSeconds($timeSpent);

        $selections = [];
        $answerRows = [];

        foreach ($questions as $question) {
            $correctIds = $question->correctAnswerIds();
            $roll = mt_rand(0, 100) / 100;

            if ($roll > 0.92) {
                // Left unanswered.
                $selected = null;
            } elseif ($roll <= $skill) {
                $selected = $correctIds;
            } else {
                $wrong = $question->answers->firstWhere('is_correct', false);
                $selected = $wrong !== null ? [$wrong->id] : $correctIds;
            }

            $selections[$question->id] = $selected;
        }

        $result = $this->scorer->score($questions, $selections);

        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'status' => AttemptStatus::Completed,
            'total_questions' => $result->totalQuestions,
            'correct_count' => $result->correctCount,
            'incorrect_count' => $result->incorrectCount,
            'unanswered_count' => $result->unansweredCount,
            'percentage' => $result->percentage,
            'points' => $result->points,
            'time_spent_seconds' => $timeSpent,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        $now = Carbon::now();
        foreach ($questions as $question) {
            $selected = $selections[$question->id];
            $outcome = $result->outcomes[$question->id] ?? null;
            $answerRows[] = [
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_answer_ids' => $selected === null ? null : json_encode(array_values($selected)),
                'is_answered' => $selected !== null,
                'is_correct' => $outcome !== null && $outcome->isAnswered ? $outcome->isCorrect : null,
                'answered_at' => $selected === null ? null : $completedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('quiz_attempt_answers')->insert($answerRows);

        ActivityLog::create([
            'user_id' => $student->id,
            'action' => 'quiz.completed',
            'description' => $student->name.' completed "'.$quiz->title.'"',
            'subject_type' => Quiz::class,
            'subject_id' => $quiz->id,
            'properties' => ['percentage' => $result->percentage, 'points' => $result->points],
            'ip_address' => '127.0.0.1',
            'created_at' => $completedAt,
        ]);
    }
}
