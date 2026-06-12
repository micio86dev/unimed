<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Full question representation for admin management and post-attempt review.
 *
 * @mixin Question
 */
class QuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'difficulty' => $this->difficulty->value,
            'difficulty_label' => $this->difficulty->label(),
            'text' => $this->text,
            'explanation' => $this->explanation,
            'image_url' => $this->image_path !== null ? Storage::disk('public')->url($this->image_path) : null,
            'is_active' => $this->is_active,
            'answers' => AnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
