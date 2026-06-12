<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;

/**
 * Build a published quiz with N scorable questions.
 */
function publishedQuiz(int $questions = 5): Quiz
{
    $quiz = Quiz::factory()->published()->create();
    $ids = Question::factory()->count($questions)->create()->pluck('id')->all();
    app(QuizService::class)->syncQuestions($quiz, $ids);

    return $quiz->fresh();
}

it('starts an attempt and never leaks correct answers', function (): void {
    actingAsStudent();
    $quiz = publishedQuiz(5);

    $response = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->assertCreated();

    $questions = $response->json('data.questions');
    expect($questions)->toHaveCount(5);

    foreach ($questions as $question) {
        foreach ($question['answers'] as $answer) {
            expect($answer)->not->toHaveKey('is_correct');
        }
    }
});

it('resumes the same in-progress attempt instead of creating a new one', function (): void {
    actingAsStudent();
    $quiz = publishedQuiz(3);

    $first = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data.attempt.id');
    $second = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data.attempt.id');

    expect($first)->toBe($second);
    expect(QuizAttempt::count())->toBe(1);
});

it('autosaves an answer and reflects it on resume', function (): void {
    actingAsStudent();
    $quiz = publishedQuiz(3);
    $payload = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data');
    $attemptId = $payload['attempt']['id'];
    $question = $payload['questions'][0];

    $this->patchJson("/api/attempts/{$attemptId}/answers", [
        'question_id' => $question['id'],
        'selected_answer_ids' => [$question['answers'][0]['id']],
    ])->assertOk();

    $resumed = $this->getJson("/api/attempts/{$attemptId}")->json('data.questions');
    $answered = collect($resumed)->firstWhere('id', $question['id']);
    expect($answered['is_answered'])->toBeTrue()
        ->and($answered['selected_answer_ids'])->toBe([$question['answers'][0]['id']]);
});

it('scores a fully-correct submission as 100%', function (): void {
    actingAsStudent();
    $quiz = publishedQuiz(4);
    $payload = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data');
    $attemptId = $payload['attempt']['id'];

    foreach ($quiz->questions as $question) {
        $correctId = $question->correctAnswerIds()[0];
        $this->patchJson("/api/attempts/{$attemptId}/answers", [
            'question_id' => $question->id,
            'selected_answer_ids' => [$correctId],
        ])->assertOk();
    }

    $result = $this->postJson("/api/attempts/{$attemptId}/submit", ['time_spent_seconds' => 120])
        ->assertOk()
        ->json('data');

    expect($result['attempt']['correct_count'])->toBe(4)
        ->and((float) $result['attempt']['percentage'])->toBe(100.0)
        ->and($result['attempt']['status'])->toBe('completed')
        ->and($result['review'])->toHaveCount(4)
        ->and($result['subject_breakdown'])->not->toBeEmpty();
});

it('prevents submitting an attempt twice', function (): void {
    actingAsStudent();
    $quiz = publishedQuiz(2);
    $attemptId = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data.attempt.id');

    $this->postJson("/api/attempts/{$attemptId}/submit")->assertOk();
    $this->postJson("/api/attempts/{$attemptId}/submit")->assertStatus(409);
});

it('forbids acting on an attempt that belongs to another student', function (): void {
    $owner = createStudent();
    $quiz = publishedQuiz(2);
    \Laravel\Sanctum\Sanctum::actingAs($owner);
    $attemptId = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data.attempt.id');

    $intruder = createStudent();
    \Laravel\Sanctum\Sanctum::actingAs($intruder);

    $this->getJson("/api/attempts/{$attemptId}")->assertForbidden();
    $this->postJson("/api/attempts/{$attemptId}/submit")->assertForbidden();
});

it('updates the leaderboard after submitting', function (): void {
    $student = actingAsStudent();
    $quiz = publishedQuiz(3);
    $attemptId = $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data.attempt.id');

    foreach ($quiz->questions as $question) {
        $this->patchJson("/api/attempts/{$attemptId}/answers", [
            'question_id' => $question->id,
            'selected_answer_ids' => [$question->correctAnswerIds()[0]],
        ]);
    }
    $this->postJson("/api/attempts/{$attemptId}/submit")->assertOk();

    $ranking = $this->getJson('/api/rankings/me')->assertOk()->json('data.ranking');

    expect($ranking['quizzes_completed'])->toBe(1)
        ->and((float) $ranking['best_score'])->toBe(100.0);
});

it('rejects starting an attempt for an unpublished quiz', function (): void {
    actingAsStudent();
    $quiz = Quiz::factory()->draft()->create();
    Question::factory()->count(3)->create()->each(fn ($q) => $quiz->questions()->attach($q));

    $this->postJson('/api/attempts', ['quiz_id' => $quiz->id])->assertNotFound();
});
