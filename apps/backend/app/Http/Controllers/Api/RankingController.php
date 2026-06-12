<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RankingResource;
use App\Models\Ranking;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RankingController extends Controller
{
    /**
     * The leaderboard, ordered by position.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $rankings = Ranking::query()
            ->with('user:id,name')
            ->where('quizzes_completed', '>', 0)
            ->orderBy('position')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return RankingResource::collection($rankings);
    }

    /**
     * The authenticated student's own ranking entry.
     */
    public function me(Request $request): JsonResponse
    {
        $ranking = Ranking::query()
            ->with('user:id,name')
            ->where('user_id', $request->user()->id)
            ->first();

        return ApiResponse::success([
            'ranking' => $ranking !== null ? new RankingResource($ranking) : null,
            'total_participants' => Ranking::where('quizzes_completed', '>', 0)->count(),
        ]);
    }
}
