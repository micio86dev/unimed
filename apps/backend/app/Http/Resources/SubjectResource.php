<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subject
 */
class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_it' => $this->name_it,
            'slug' => $this->slug,
            'description' => $this->description,
            'description_it' => $this->description_it,
            'color' => $this->color,
            'icon' => $this->icon,
            'position' => $this->position,
            'questions_count' => $this->whenCounted('questions'),
        ];
    }
}
