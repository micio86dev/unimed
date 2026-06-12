<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectController extends Controller
{
    /**
     * List all subjects with their active question counts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $subjects = Subject::query()
            ->withCount(['questions' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('position')
            ->get();

        return SubjectResource::collection($subjects);
    }
}
