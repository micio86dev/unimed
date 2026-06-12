<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    /** @use HasFactory<\Database\Factories\RankingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quizzes_completed',
        'total_points',
        'average_score',
        'best_score',
        'average_time_seconds',
        'position',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'quizzes_completed' => 'integer',
            'total_points' => 'integer',
            'average_score' => 'float',
            'best_score' => 'float',
            'average_time_seconds' => 'integer',
            'position' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
