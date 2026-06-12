<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Summary representation of a quiz attempt (history lists, result headers).
 *
 * @mixin QuizAttempt
 */
class AttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total_questions' => $this->total_questions,
            'correct_count' => $this->correct_count,
            'incorrect_count' => $this->incorrect_count,
            'unanswered_count' => $this->unanswered_count,
            'percentage' => (float) $this->percentage,
            'points' => $this->points,
            'time_spent_seconds' => $this->time_spent_seconds,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
