<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creation / updating of quizzes — both manual (explicit question list) and
 * automatically generated (drawn from filters).
 */
final class QuizService
{
    /**
     * Create a quiz from an explicit, ordered list of question ids.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $questionIds
     */
    public function createManual(array $data, array $questionIds, ?User $author = null): Quiz
    {
        return DB::transaction(function () use ($data, $questionIds, $author): Quiz {
            $quiz = Quiz::create([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'description' => $data['description'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'difficulty' => $data['difficulty'] ?? null,
                'is_published' => $data['is_published'] ?? false,
                'is_auto_generated' => false,
                'settings' => null,
                'created_by' => $author?->id,
                'question_count' => 0,
            ]);

            $this->syncQuestions($quiz, $questionIds);

            return $quiz->fresh(['questions']);
        });
    }

    /**
     * Auto-generate a quiz by drawing random active questions matching the
     * supplied filters (subjects + optional difficulty).
     *
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data, ?User $author = null): Quiz
    {
        $count = (int) ($data['question_count'] ?? 20);
        $subjectIds = $data['subject_ids'] ?? [];
        $difficulty = $data['difficulty'] ?? null;

        $questionIds = Question::query()
            ->active()
            ->when($subjectIds !== [], fn ($q) => $q->whereIn('subject_id', $subjectIds))
            ->when($difficulty !== null, fn ($q) => $q->where('difficulty', $difficulty))
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->all();

        return DB::transaction(function () use ($data, $questionIds, $subjectIds, $difficulty, $author): Quiz {
            $quiz = Quiz::create([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title']),
                'description' => $data['description'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'difficulty' => $difficulty,
                'is_published' => $data['is_published'] ?? false,
                'is_auto_generated' => true,
                'settings' => [
                    'subject_ids' => array_values($subjectIds),
                    'difficulty' => $difficulty,
                    'requested_count' => (int) ($data['question_count'] ?? 20),
                ],
                'created_by' => $author?->id,
                'question_count' => 0,
            ]);

            $this->syncQuestions($quiz, $questionIds);

            return $quiz->fresh(['questions']);
        });
    }

    /**
     * Replace a quiz's questions with the given ordered list and refresh the
     * cached question_count.
     *
     * @param  array<int, int>  $questionIds
     */
    public function syncQuestions(Quiz $quiz, array $questionIds): void
    {
        $sync = [];
        $position = 0;
        foreach ($questionIds as $id) {
            $sync[$id] = ['position' => $position++];
        }

        $quiz->questions()->sync($sync);
        $quiz->update(['question_count' => count($sync)]);
    }

    public function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'quiz';
        $slug = $base;
        $suffix = 1;

        while (Quiz::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
