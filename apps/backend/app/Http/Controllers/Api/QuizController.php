<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Quiz;
use App\Services\ActivityLogger;
use App\Services\QuizService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * List quizzes. Students see published quizzes only; admins can request the
     * full set (including drafts) with ?scope=all.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $canManage = $request->user()->can('manage quizzes');
        $includeAll = $canManage && $request->string('scope')->value() === 'all';

        $quizzes = Quiz::query()
            ->withCount('attempts')
            ->when(! $includeAll, fn ($q) => $q->published())
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->string('difficulty')))
            ->latest('id')
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return QuizResource::collection($quizzes);
    }

    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $canManage = $request->user()->can('manage quizzes');

        if (! $canManage && ! $quiz->is_published) {
            return ApiResponse::error('Quiz not found.', 404);
        }

        // Only admins get the full question set (with correct answers).
        if ($canManage) {
            $quiz->load(['questions.answers', 'questions.subject'])->loadCount('attempts');
        } else {
            $quiz->loadCount('attempts');
        }

        return ApiResponse::success(new QuizResource($quiz));
    }

    public function store(StoreQuizRequest $request): JsonResponse
    {
        $data = $request->validated();

        $quiz = $data['mode'] === 'auto'
            ? $this->quizzes->generate($data, $request->user())
            : $this->quizzes->createManual($data, $data['question_ids'] ?? [], $request->user());

        $this->activity->log('quiz.created', 'Created quiz "'.$quiz->title.'"', $quiz);

        return ApiResponse::success(
            new QuizResource($quiz->load(['questions.subject'])),
            'Quiz created.',
            201,
        );
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $quiz->fill($request->safe()->except('question_ids'));
        $quiz->save();

        if ($request->has('question_ids')) {
            $this->quizzes->syncQuestions($quiz, $request->input('question_ids', []));
        }

        $this->activity->log('quiz.updated', 'Updated quiz "'.$quiz->title.'"', $quiz);

        return ApiResponse::success(
            new QuizResource($quiz->fresh(['questions.subject'])),
            'Quiz updated.',
        );
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        $this->activity->log('quiz.deleted', 'Deleted quiz "'.$quiz->title.'"', $quiz);
        $quiz->delete();

        return ApiResponse::message('Quiz deleted.');
    }
}
