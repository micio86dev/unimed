<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    /**
     * Analytics for the authenticated student's dashboard.
     */
    public function student(Request $request): JsonResponse
    {
        return ApiResponse::success($this->analytics->forStudent($request->user()));
    }

    /**
     * Platform-wide analytics for the admin dashboard.
     */
    public function admin(): JsonResponse
    {
        return ApiResponse::success($this->analytics->forAdmin());
    }
}
