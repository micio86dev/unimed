<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Difficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'difficulty' => ['nullable', Rule::in(Difficulty::values())],
            'is_published' => ['sometimes', 'boolean'],
            'mode' => ['required', Rule::in(['manual', 'auto'])],

            // Manual mode: explicit, ordered question ids.
            'question_ids' => ['required_if:mode,manual', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'distinct', 'exists:questions,id'],

            // Auto mode: generation filters.
            'question_count' => ['required_if:mode,auto', 'integer', 'min:1', 'max:120'],
            'subject_ids' => ['sometimes', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];
    }
}
