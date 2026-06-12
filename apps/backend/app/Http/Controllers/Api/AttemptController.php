<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Quiz\StartQuizAttempt;
use App\Actions\Quiz\SubmitQuizAttempt;
use App\Enums\AttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAnswerRequest;
use App\Http\Resources\AttemptResource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use App\Support\AttemptPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttemptController extends Controller
{
    public function __construct(
        private readonly StartQuizAttempt $starter,
        private readonly SubmitQuizAttempt $submitter,
        private readonly AttemptPresenter $presenter,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * The current student's attempt history.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $attempts = QuizAttempt::query()
            ->with('quiz:id,title,slug,time_limit_minutes,question_count')
            ->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return AttemptResource::collection($attempts);
    }

    /**
     * Start (or resume) an attempt for a quiz.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quiz_id' => ['required', 'integer', 'exists:quizzes,id'],
        ]);

        $quiz = Quiz::query()->published()->findOrFail($validated['quiz_id']);

        if ($quiz->question_count === 0) {
            return ApiResponse::error(__('messages.attempt.quiz_empty'), 422);
        }

        $attempt = $this->starter->handle($request->user(), $quiz);

        $this->activity->log('attempt.started', 'Started "'.$quiz->title.'"', $attempt);

        return ApiResponse::success($this->presenter->take($attempt), __('messages.attempt.started'), 201);
    }

    /**
     * Resume an in-progress attempt (take payload — no correct answers leaked).
     */
    public function show(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $attempt);

        if ($attempt->status !== AttemptStatus::InProgress) {
            return ApiResponse::error(__('messages.attempt.already_submitted'), 409);
        }

        return ApiResponse::success($this->presenter->take($attempt));
    }

    /**
     * Autosave a single answer.
     */
    public function saveAnswer(SaveAnswerRequest $request, QuizAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $attempt);
        $this->ensureInProgress($attempt);

        $answer = $attempt->answers()->where('question_id', $request->integer('question_id'))->first();

        if ($answer === null) {
            return ApiResponse::error(__('messages.attempt.question_not_in_attempt'), 422);
        }

        $selected = array_values(array_map('intval', $request->input('selected_answer_ids', [])));

        $answer->update([
            'selected_answer_ids' => $selected === [] ? null : $selected,
            'is_answered' => $selected !== [],
            'answered_at' => $selected === [] ? null : now(),
        ]);

        return ApiResponse::success([
            'question_id' => $answer->question_id,
            'is_answered' => $answer->is_answered,
        ], __('messages.attempt.saved'));
    }

    /**
     * Submit the attempt: score it and return the detailed result.
     */
    public function submit(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $attempt);
        $this->ensureInProgress($attempt);

        $timeSpent = $request->integer('time_spent_seconds') ?: null;
        $attempt = $this->submitter->handle($attempt, $timeSpent);

        $this->activity->log(
            'attempt.completed',
            'Completed "'.$attempt->quiz->title.'" — '.$attempt->percentage.'%',
            $attempt,
            ['percentage' => $attempt->percentage, 'points' => $attempt->points],
        );

        return ApiResponse::success($this->presenter->result($attempt), __('messages.attempt.submitted'));
    }

    /**
     * Detailed result for a completed attempt.
     */
    public function result(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->ensureOwner($request, $attempt);

        if ($attempt->status !== AttemptStatus::Completed) {
            return ApiResponse::error(__('messages.attempt.not_completed'), 409);
        }

        return ApiResponse::success($this->presenter->result($attempt));
    }

    private function ensureOwner(Request $request, QuizAttempt $attempt): void
    {
        abort_unless($attempt->user_id === $request->user()->id, 403, __('messages.attempt.not_owner'));
    }

    private function ensureInProgress(QuizAttempt $attempt): void
    {
        abort_unless($attempt->status === AttemptStatus::InProgress, 409, __('messages.attempt.not_in_progress'));
    }
}
