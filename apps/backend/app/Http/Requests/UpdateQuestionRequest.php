<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Updates replace the whole question (including its answer set), so the
 * validation rules match the store request.
 */
class UpdateQuestionRequest extends StoreQuestionRequest
{
}
