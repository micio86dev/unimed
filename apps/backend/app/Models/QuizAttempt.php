<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'status',
        'total_questions',
        'correct_count',
        'incorrect_count',
        'unanswered_count',
        'percentage',
        'points',
        'time_spent_seconds',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'total_questions' => 'integer',
            'correct_count' => 'integer',
            'incorrect_count' => 'integer',
            'unanswered_count' => 'integer',
            'percentage' => 'float',
            'points' => 'integer',
            'time_spent_seconds' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return HasMany<QuizAttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === AttemptStatus::Completed;
    }

    /**
     * @param  Builder<QuizAttempt>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', AttemptStatus::Completed->value);
    }
}
