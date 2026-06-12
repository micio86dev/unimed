<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Subject;

it('lets an admin create a valid single-choice question', function (): void {
    actingAsAdmin();
    $subject = Subject::factory()->create();

    $response = $this->postJson('/api/admin/questions', [
        'subject_id' => $subject->id,
        'type' => 'single',
        'difficulty' => 'medium',
        'text' => 'What is the powerhouse of the cell?',
        'explanation' => 'Mitochondria produce ATP.',
        'answers' => [
            ['text' => 'Mitochondrion', 'is_correct' => true],
            ['text' => 'Ribosome', 'is_correct' => false],
            ['text' => 'Nucleus', 'is_correct' => false],
        ],
    ]);

    $response->assertCreated()->assertJsonPath('data.text', 'What is the powerhouse of the cell?');
    expect(Question::count())->toBe(1)
        ->and(Question::first()->answers()->where('is_correct', true)->count())->toBe(1);
});

it('rejects a single-choice question without exactly one correct answer', function (): void {
    actingAsAdmin();
    $subject = Subject::factory()->create();

    $this->postJson('/api/admin/questions', [
        'subject_id' => $subject->id,
        'type' => 'single',
        'difficulty' => 'easy',
        'text' => 'Pick one',
        'answers' => [
            ['text' => 'A', 'is_correct' => true],
            ['text' => 'B', 'is_correct' => true],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors('answers');
});

it('rejects a question with no correct answer', function (): void {
    actingAsAdmin();
    $subject = Subject::factory()->create();

    $this->postJson('/api/admin/questions', [
        'subject_id' => $subject->id,
        'type' => 'single',
        'difficulty' => 'easy',
        'text' => 'Pick one',
        'answers' => [
            ['text' => 'A', 'is_correct' => false],
            ['text' => 'B', 'is_correct' => false],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors('answers');
});

it('replaces the answer set when updating a question', function (): void {
    actingAsAdmin();
    $question = Question::factory()->create();
    $originalAnswerIds = $question->answers->pluck('id');

    $this->putJson("/api/admin/questions/{$question->id}", [
        'subject_id' => $question->subject_id,
        'type' => 'single',
        'difficulty' => 'hard',
        'text' => 'Updated stem',
        'answers' => [
            ['text' => 'New correct', 'is_correct' => true],
            ['text' => 'New wrong', 'is_correct' => false],
        ],
    ])->assertOk()->assertJsonPath('data.difficulty', 'hard');

    $question->refresh();
    expect($question->answers)->toHaveCount(2)
        ->and($question->answers->pluck('id')->intersect($originalAnswerIds))->toBeEmpty();
});

it('deletes a question', function (): void {
    actingAsAdmin();
    $question = Question::factory()->create();

    $this->deleteJson("/api/admin/questions/{$question->id}")->assertOk();
    expect(Question::find($question->id))->toBeNull();
});

it('filters questions by subject, difficulty and search term', function (): void {
    actingAsAdmin();
    $bio = Subject::factory()->create(['name' => 'Biology']);
    $chem = Subject::factory()->create(['name' => 'Chemistry']);

    Question::factory()->hard()->create(['subject_id' => $bio->id, 'text' => 'Mitochondria function']);
    Question::factory()->easy()->create(['subject_id' => $bio->id, 'text' => 'Cell wall basics']);
    Question::factory()->create(['subject_id' => $chem->id, 'text' => 'Atomic structure']);

    $this->getJson("/api/admin/questions?subject_id={$bio->id}")->assertOk()
        ->assertJsonCount(2, 'data');

    $this->getJson("/api/admin/questions?subject_id={$bio->id}&difficulty=hard")->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/admin/questions?search=Atomic')->assertOk()
        ->assertJsonCount(1, 'data');
});
