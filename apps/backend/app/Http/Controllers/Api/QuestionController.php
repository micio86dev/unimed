<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Paginated, filterable, searchable list of questions (admin).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->with(['subject', 'answers'])
            ->withCount('answers')
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->string('difficulty')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($q) => $q->search((string) $request->string('search')))
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return QuestionResource::collection($questions);
    }

    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $question = DB::transaction(function () use ($request): Question {
            $question = Question::create([
                'subject_id' => $request->integer('subject_id'),
                'type' => $request->enum('type', QuestionType::class),
                'difficulty' => $request->enum('difficulty', Difficulty::class),
                'text' => (string) $request->input('text'),
                'text_it' => $request->input('text_it'),
                'explanation' => $request->input('explanation'),
                'explanation_it' => $request->input('explanation_it'),
                'image_path' => $request->input('image_path'),
                'is_active' => $request->boolean('is_active', true),
                'created_by' => $request->user()->id,
            ]);

            $this->syncAnswers($question, $request->input('answers', []));

            return $question;
        });

        $this->activity->log('question.created', 'Created a question', $question);

        return ApiResponse::success(
            new QuestionResource($question->load(['subject', 'answers'])),
            __('messages.question.created'),
            201,
        );
    }

    public function show(Question $question): JsonResponse
    {
        return ApiResponse::success(new QuestionResource($question->load(['subject', 'answers'])));
    }

    public function update(UpdateQuestionRequest $request, Question $question): JsonResponse
    {
        DB::transaction(function () use ($request, $question): void {
            $question->update([
                'subject_id' => $request->integer('subject_id'),
                'type' => $request->enum('type', QuestionType::class),
                'difficulty' => $request->enum('difficulty', Difficulty::class),
                'text' => (string) $request->input('text'),
                'text_it' => $request->input('text_it'),
                'explanation' => $request->input('explanation'),
                'explanation_it' => $request->input('explanation_it'),
                'image_path' => $request->input('image_path'),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $question->answers()->delete();
            $this->syncAnswers($question, $request->input('answers', []));
        });

        $this->activity->log('question.updated', 'Updated a question', $question);

        return ApiResponse::success(
            new QuestionResource($question->fresh(['subject', 'answers'])),
            __('messages.question.updated'),
        );
    }

    public function destroy(Question $question): JsonResponse
    {
        $this->activity->log('question.deleted', 'Deleted a question', $question);
        $question->delete();

        return ApiResponse::message(__('messages.question.deleted'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $answers
     */
    private function syncAnswers(Question $question, array $answers): void
    {
        $position = 0;
        $rows = array_map(static function (array $answer) use (&$position): array {
            return [
                'text' => $answer['text'],
                'text_it' => $answer['text_it'] ?? null,
                'is_correct' => filter_var($answer['is_correct'] ?? false, FILTER_VALIDATE_BOOL),
                'position' => $position++,
            ];
        }, $answers);

        $question->answers()->createMany($rows);
    }
}
