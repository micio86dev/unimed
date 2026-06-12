<?php

declare(strict_types=1);

use App\Domain\Quiz\QuizScoringService;
use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionAnswer;
use Illuminate\Support\Collection;

/**
 * Build a question with an explicit number of correct / incorrect answers.
 */
function scoringQuestion(string $difficulty = 'medium', string $type = 'single', int $correct = 1, int $wrong = 3): Question
{
    $question = Question::factory()->create([
        'difficulty' => Difficulty::from($difficulty),
        'type' => QuestionType::from($type),
    ]);

    $question->answers()->delete();

    for ($i = 0; $i < $correct; $i++) {
        QuestionAnswer::factory()->correct()->create(['question_id' => $question->id, 'position' => $i]);
    }
    for ($i = 0; $i < $wrong; $i++) {
        QuestionAnswer::factory()->create(['question_id' => $question->id, 'position' => $correct + $i]);
    }

    return $question->load('answers');
}

beforeEach(function (): void {
    $this->scorer = new QuizScoringService();
});

it('scores a perfect single-choice attempt as 100%', function (): void {
    $q = scoringQuestion('medium', 'single', 1, 3);
    $correctId = $q->correctAnswerIds()[0];

    $result = $this->scorer->score(new Collection([$q]), [$q->id => [$correctId]]);

    expect($result->correctCount)->toBe(1)
        ->and($result->incorrectCount)->toBe(0)
        ->and($result->unansweredCount)->toBe(0)
        ->and($result->percentage)->toBe(100.0);
});

it('marks a wrong single-choice selection as incorrect', function (): void {
    $q = scoringQuestion('medium', 'single', 1, 3);
    $wrongId = $q->answers->firstWhere('is_correct', false)->id;

    $result = $this->scorer->score(new Collection([$q]), [$q->id => [$wrongId]]);

    expect($result->correctCount)->toBe(0)
        ->and($result->incorrectCount)->toBe(1)
        ->and($result->percentage)->toBe(0.0);
});

it('counts unanswered questions separately', function (): void {
    $q = scoringQuestion();

    $result = $this->scorer->score(new Collection([$q]), [$q->id => null]);

    expect($result->unansweredCount)->toBe(1)
        ->and($result->incorrectCount)->toBe(0)
        ->and($result->outcomes[$q->id]->isAnswered)->toBeFalse();
});

it('requires an exact set match for multiple-choice questions', function (): void {
    $q = scoringQuestion('hard', 'multiple', correct: 2, wrong: 2);
    $correctIds = $q->correctAnswerIds();

    // Only one of the two correct answers selected → not correct.
    $partial = $this->scorer->score(new Collection([$q]), [$q->id => [$correctIds[0]]]);
    expect($partial->correctCount)->toBe(0);

    // Both correct answers selected → correct.
    $full = $this->scorer->score(new Collection([$q]), [$q->id => $correctIds]);
    expect($full->correctCount)->toBe(1);

    // Correct answers plus an extra wrong one → not correct.
    $wrongId = $q->answers->firstWhere('is_correct', false)->id;
    $extra = $this->scorer->score(new Collection([$q]), [$q->id => [...$correctIds, $wrongId]]);
    expect($extra->correctCount)->toBe(0);
});

it('awards points weighted by difficulty', function (): void {
    $easy = scoringQuestion('easy');
    $medium = scoringQuestion('medium');
    $hard = scoringQuestion('hard');

    $selections = [
        $easy->id => [$easy->correctAnswerIds()[0]],
        $medium->id => [$medium->correctAnswerIds()[0]],
        $hard->id => [$hard->correctAnswerIds()[0]],
    ];

    $result = $this->scorer->score(new Collection([$easy, $medium, $hard]), $selections);

    // easy 10 + medium 20 + hard 30 = 60
    expect($result->points)->toBe(60)
        ->and($result->correctCount)->toBe(3)
        ->and($result->percentage)->toBe(100.0);
});

it('computes the right percentage for a partially correct attempt', function (): void {
    $questions = collect(range(1, 4))->map(fn () => scoringQuestion());
    $selections = [];
    foreach ($questions as $i => $q) {
        // Answer the first two correctly, the rest wrong.
        $selections[$q->id] = $i < 2
            ? [$q->correctAnswerIds()[0]]
            : [$q->answers->firstWhere('is_correct', false)->id];
    }

    $result = $this->scorer->score($questions->values(), $selections);

    expect($result->correctCount)->toBe(2)
        ->and($result->percentage)->toBe(50.0);
});
