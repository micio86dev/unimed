<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'type' => ['required', Rule::in(QuestionType::values())],
            'difficulty' => ['required', Rule::in(Difficulty::values())],
            'text' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'answers' => ['required', 'array', 'min:2', 'max:6'],
            'answers.*.text' => ['required', 'string', 'max:2000'],
            'answers.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $answers = $this->input('answers', []);
            $correct = collect($answers)->filter(fn ($a) => filter_var($a['is_correct'] ?? false, FILTER_VALIDATE_BOOL))->count();

            if ($correct < 1) {
                $validator->errors()->add('answers', 'At least one answer must be marked as correct.');

                return;
            }

            if ($this->input('type') === QuestionType::Single->value && $correct !== 1) {
                $validator->errors()->add('answers', 'Single-choice questions must have exactly one correct answer.');
            }
        });
    }
}
