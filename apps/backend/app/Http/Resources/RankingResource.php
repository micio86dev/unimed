<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Ranking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ranking
 */
class RankingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'position' => $this->position,
            'user_id' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user->name),
            'quizzes_completed' => $this->quizzes_completed,
            'total_points' => $this->total_points,
            'average_score' => (float) $this->average_score,
            'best_score' => (float) $this->best_score,
            'average_time_seconds' => $this->average_time_seconds,
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'is_current_user' => $request->user()?->id === $this->user_id,
        ];
    }
}
