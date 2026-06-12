<?php

declare(strict_types=1);

namespace App\Actions\Quiz;

use App\Enums\AttemptStatus;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class StartQuizAttempt
{
    /**
     * Start a new attempt for the given user + quiz, or resume the existing
     * in-progress one. Creates a placeholder answer row per quiz question so
     * autosave can update in place.
     */
    public function handle(User $user, Quiz $quiz): QuizAttempt
    {
        $existing = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', AttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing->load(['answers', 'quiz']);
        }

        $quiz->loadMissing('questions');
        $questions = $quiz->questions;

        return DB::transaction(function () use ($user, $quiz, $questions): QuizAttempt {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'status' => AttemptStatus::InProgress,
                'total_questions' => $questions->count(),
                'started_at' => now(),
            ]);

            $rows = $questions->map(static fn ($question): array => [
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'selected_answer_ids' => null,
                'is_answered' => false,
                'is_correct' => null,
                'answered_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if ($rows !== []) {
                DB::table('quiz_attempt_answers')->insert($rows);
            }

            return $attempt->load(['answers', 'quiz']);
        });
    }
}
