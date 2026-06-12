<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Upload an image (e.g. a question figure) to the public disk.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:4096'],
        ]);

        $path = $request->file('image')->store('questions', 'public');

        return ApiResponse::success([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 'Image uploaded.', 201);
    }
}
