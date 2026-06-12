<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the composite payloads for taking and reviewing a quiz attempt.
 *
 * The "take" payload is deliberately sanitised: it never exposes which answers
 * are correct, nor the explanation. The "result" payload reveals everything.
 */
final class AttemptPresenter
{
    /**
     * Payload sent while a student is taking (or resuming) an attempt.
     *
     * @return array<string, mixed>
     */
    public function take(QuizAttempt $attempt): array
    {
        $attempt->loadMissing(['quiz.questions.answers', 'quiz.questions.subject', 'answers']);

        $saved = $attempt->answers->mapWithKeys(static fn ($a): array => [
            $a->question_id => [
                'selected_answer_ids' => $a->selected_answer_ids ?? [],
                'is_answered' => $a->is_answered,
            ],
        ])->all();

        $questions = $attempt->quiz->questions->values()->map(function (Question $q, int $index) use ($saved): array {
            return [
                'id' => $q->id,
                'position' => $index + 1,
                'type' => $q->type->value,
                'difficulty' => $q->difficulty->value,
                'subject' => $this->subjectStub($q),
                'text' => $q->text,
                'image_url' => $q->image_path !== null ? Storage::disk('public')->url($q->image_path) : null,
                'answers' => $q->answers->map(static fn ($a): array => [
                    'id' => $a->id,
                    'text' => $a->text,
                ])->values()->all(),
                'selected_answer_ids' => $saved[$q->id]['selected_answer_ids'] ?? [],
                'is_answered' => $saved[$q->id]['is_answered'] ?? false,
            ];
        })->all();

        return [
            'attempt' => $this->summary($attempt),
            'questions' => $questions,
        ];
    }

    /**
     * Detailed result payload after submission: stats, subject breakdown and
     * a full per-question review.
     *
     * @return array<string, mixed>
     */
    public function result(QuizAttempt $attempt): array
    {
        $attempt->loadMissing(['quiz.questions.answers', 'quiz.questions.subject', 'answers']);

        $answersByQuestion = $attempt->answers->keyBy('question_id');

        $review = [];
        $bySubject = [];

        foreach ($attempt->quiz->questions as $index => $question) {
            $answer = $answersByQuestion->get($question->id);
            $selected = $answer?->selected_answer_ids ?? [];
            $isAnswered = (bool) ($answer?->is_answered);
            $isCorrect = (bool) ($answer?->is_correct);

            $review[] = [
                'position' => $index + 1,
                'question' => [
                    'id' => $question->id,
                    'text' => $question->text,
                    'type' => $question->type->value,
                    'difficulty' => $question->difficulty->value,
                    'explanation' => $question->explanation,
                    'subject' => $this->subjectStub($question),
                ],
                'options' => $question->answers->map(static fn ($a): array => [
                    'id' => $a->id,
                    'text' => $a->text,
                    'is_correct' => $a->is_correct,
                ])->values()->all(),
                'selected_answer_ids' => array_values($selected),
                'correct_answer_ids' => $question->correctAnswerIds(),
                'is_answered' => $isAnswered,
                'is_correct' => $isCorrect,
            ];

            $slug = $question->subject?->slug ?? 'unknown';
            $bySubject[$slug] ??= [
                'subject' => $question->subject?->name ?? 'Unknown',
                'slug' => $slug,
                'color' => $question->subject?->color ?? '#94A3B8',
                'total' => 0,
                'correct' => 0,
            ];
            $bySubject[$slug]['total']++;
            if ($isCorrect) {
                $bySubject[$slug]['correct']++;
            }
        }

        $subjectBreakdown = array_values(array_map(static function (array $row): array {
            $row['accuracy'] = $row['total'] > 0 ? round(($row['correct'] / $row['total']) * 100, 1) : 0.0;

            return $row;
        }, $bySubject));

        return [
            'attempt' => $this->summary($attempt),
            'subject_breakdown' => $subjectBreakdown,
            'review' => $review,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(QuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
                'slug' => $attempt->quiz->slug,
                'time_limit_minutes' => $attempt->quiz->time_limit_minutes,
                'question_count' => $attempt->quiz->question_count,
            ],
            'status' => $attempt->status->value,
            'total_questions' => $attempt->total_questions,
            'correct_count' => $attempt->correct_count,
            'incorrect_count' => $attempt->incorrect_count,
            'unanswered_count' => $attempt->unanswered_count,
            'percentage' => (float) $attempt->percentage,
            'points' => $attempt->points,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'completed_at' => $attempt->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subjectStub(Question $question): array
    {
        return [
            'id' => $question->subject?->id,
            'name' => $question->subject?->name,
            'slug' => $question->subject?->slug,
            'color' => $question->subject?->color,
        ];
    }
}
