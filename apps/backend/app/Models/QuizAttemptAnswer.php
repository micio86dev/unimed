<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_answer_ids',
        'is_answered',
        'is_correct',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'selected_answer_ids' => 'array',
            'is_answered' => 'boolean',
            'is_correct' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QuizAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Normalised, sorted list of the answer ids the student selected.
     *
     * @return array<int, int>
     */
    public function selectedIds(): array
    {
        $ids = $this->selected_answer_ids ?? [];

        return collect($ids)->map(static fn ($id): int => (int) $id)->sort()->values()->all();
    }
}
