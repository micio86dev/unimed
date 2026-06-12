<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full answer representation — includes correctness. ONLY use in admin/review
 * contexts, never when serving a quiz for a student to take.
 *
 * @mixin QuestionAnswer
 */
class AnswerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'is_correct' => $this->is_correct,
            'position' => $this->position,
        ];
    }
}
