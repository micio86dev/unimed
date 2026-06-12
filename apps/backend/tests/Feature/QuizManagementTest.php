<?php

declare(strict_types=1);

use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;

it('creates a manual quiz from an explicit question list', function (): void {
    actingAsAdmin();
    $ids = Question::factory()->count(5)->create()->pluck('id')->all();

    $response = $this->postJson('/api/admin/quizzes', [
        'title' => 'My Manual Quiz',
        'description' => 'Hand-picked questions',
        'time_limit_minutes' => 30,
        'mode' => 'manual',
        'is_published' => true,
        'question_ids' => $ids,
    ])->assertCreated();

    expect($response->json('data.question_count'))->toBe(5)
        ->and($response->json('data.is_auto_generated'))->toBeFalse();
});

it('auto-generates a quiz from subject filters', function (): void {
    actingAsAdmin();
    $subject = Subject::factory()->create();
    Question::factory()->count(20)->create(['subject_id' => $subject->id]);
    Question::factory()->count(20)->create(); // other subjects

    $response = $this->postJson('/api/admin/quizzes', [
        'title' => 'Auto Subject Quiz',
        'mode' => 'auto',
        'question_count' => 10,
        'subject_ids' => [$subject->id],
        'is_published' => true,
    ])->assertCreated();

    $quiz = Quiz::find($response->json('data.id'));
    expect($quiz->question_count)->toBe(10)
        ->and($quiz->is_auto_generated)->toBeTrue()
        ->and($quiz->questions->pluck('subject_id')->unique()->all())->toBe([$subject->id]);
});

it('requires question_ids in manual mode', function (): void {
    actingAsAdmin();

    $this->postJson('/api/admin/quizzes', [
        'title' => 'Bad Quiz',
        'mode' => 'manual',
    ])->assertStatus(422)->assertJsonValidationErrors('question_ids');
});

it('hides draft quizzes from students but shows them to admins via scope=all', function (): void {
    Quiz::factory()->published()->create(['title' => 'Public Quiz']);
    Quiz::factory()->draft()->create(['title' => 'Secret Draft']);

    actingAsStudent();
    $studentList = $this->getJson('/api/quizzes')->assertOk()->json('data');
    expect(collect($studentList)->pluck('title'))->toContain('Public Quiz')
        ->not->toContain('Secret Draft');

    actingAsAdmin();
    $adminList = $this->getJson('/api/quizzes?scope=all')->assertOk()->json('data');
    expect(collect($adminList)->pluck('title'))->toContain('Secret Draft');
});

it('lets an admin publish and unpublish a quiz', function (): void {
    actingAsAdmin();
    $quiz = Quiz::factory()->draft()->create();

    $this->patchJson("/api/admin/quizzes/{$quiz->slug}", ['is_published' => true])->assertOk();
    expect($quiz->fresh()->is_published)->toBeTrue();

    $this->patchJson("/api/admin/quizzes/{$quiz->slug}", ['is_published' => false])->assertOk();
    expect($quiz->fresh()->is_published)->toBeFalse();
});

it('lets an admin delete a quiz', function (): void {
    actingAsAdmin();
    $quiz = Quiz::factory()->create();

    $this->deleteJson("/api/admin/quizzes/{$quiz->slug}")->assertOk();
    expect(Quiz::find($quiz->id))->toBeNull();
});
