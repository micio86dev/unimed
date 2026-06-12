<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quiz
 */
class QuizResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'title_it' => $this->title_it,
            'slug' => $this->slug,
            'description' => $this->description,
            'description_it' => $this->description_it,
            'time_limit_minutes' => $this->time_limit_minutes,
            'question_count' => $this->question_count,
            'difficulty' => $this->difficulty,
            'is_published' => $this->is_published,
            'is_auto_generated' => $this->is_auto_generated,
            'settings' => $this->settings,
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'attempts_count' => $this->whenCounted('attempts'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
