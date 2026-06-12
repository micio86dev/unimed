<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Lightweight audit logger. Records meaningful user/admin actions to the
 * activity_logs table for the analytics dashboard and security auditing.
 */
final class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
    ): ActivityLog {
        $user ??= Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => Request::ip(),
        ]);
    }
}
