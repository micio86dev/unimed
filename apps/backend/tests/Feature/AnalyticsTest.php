<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Quiz;
use App\Services\QuizService;

function completeQuiz(int $questions = 4, bool $allCorrect = true): array
{
    $quiz = Quiz::factory()->published()->create();
    $ids = Question::factory()->count($questions)->create()->pluck('id')->all();
    app(QuizService::class)->syncQuestions($quiz, $ids);
    $quiz = $quiz->fresh(['questions']);

    $attemptId = test()->postJson('/api/attempts', ['quiz_id' => $quiz->id])->json('data.attempt.id');

    foreach ($quiz->questions as $i => $question) {
        $correct = $allCorrect || $i === 0;
        $answerId = $correct
            ? $question->correctAnswerIds()[0]
            : $question->answers->firstWhere('is_correct', false)->id;
        test()->patchJson("/api/attempts/{$attemptId}/answers", [
            'question_id' => $question->id,
            'selected_answer_ids' => [$answerId],
        ]);
    }

    test()->postJson("/api/attempts/{$attemptId}/submit")->assertOk();

    return [$quiz, $attemptId];
}

it('returns student analytics with KPIs and subject performance', function (): void {
    actingAsStudent();
    completeQuiz(4, allCorrect: true);

    $data = $this->getJson('/api/analytics/student')->assertOk()->json('data');

    expect($data['kpis']['completed_quizzes'])->toBe(1)
        ->and((float) $data['kpis']['best_score'])->toBe(100.0)
        ->and($data)->toHaveKeys(['subject_performance', 'recent_attempts', 'score_trend']);
});

it('returns admin analytics with platform KPIs and hardest subjects', function (): void {
    actingAsAdmin();
    completeQuiz(4, allCorrect: false);

    $data = $this->getJson('/api/admin/analytics')->assertOk()->json('data');

    expect($data['kpis'])->toHaveKeys([
        'total_users', 'total_students', 'active_users', 'completed_quizzes',
        'average_score', 'total_questions', 'total_quizzes',
    ])
        ->and($data)->toHaveKeys(['hardest_subjects', 'attempts_trend'])
        ->and($data['kpis']['completed_quizzes'])->toBeGreaterThanOrEqual(1);
});
