<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application API messages (English)
|--------------------------------------------------------------------------
| User-facing strings returned by the API. The active locale is resolved
| per-request from the Accept-Language header (see SetLocale middleware).
*/

return [
    'auth' => [
        'invalid_credentials' => 'These credentials do not match our records.',
        'account_disabled' => 'Your account has been disabled. Please contact an administrator.',
        'signed_in' => 'Signed in successfully.',
        'registered' => 'Welcome to UniMed! Your account is ready.',
        'signed_out' => 'Signed out successfully.',
        'reset_link_sent' => 'If that email exists, a reset link has been sent.',
        'password_reset' => 'Your password has been reset. You can now sign in.',
        'unauthenticated' => 'Unauthenticated.',
    ],

    'attempt' => [
        'quiz_empty' => 'This quiz has no questions yet.',
        'started' => 'Attempt started.',
        'already_submitted' => 'This attempt has already been submitted.',
        'question_not_in_attempt' => 'That question is not part of this attempt.',
        'saved' => 'Saved.',
        'submitted' => 'Attempt submitted.',
        'not_completed' => 'This attempt has not been completed yet.',
        'not_owner' => 'This attempt does not belong to you.',
        'not_in_progress' => 'This attempt is no longer in progress.',
    ],

    'question' => [
        'created' => 'Question created.',
        'updated' => 'Question updated.',
        'deleted' => 'Question deleted.',
        'at_least_one_correct' => 'At least one answer must be marked as correct.',
        'single_choice_one_correct' => 'Single-choice questions must have exactly one correct answer.',
    ],

    'quiz' => [
        'created' => 'Quiz created.',
        'updated' => 'Quiz updated.',
        'deleted' => 'Quiz deleted.',
    ],

    'upload' => [
        'image_uploaded' => 'Image uploaded.',
    ],

    'common' => [
        'not_found' => 'Resource not found.',
    ],
];
