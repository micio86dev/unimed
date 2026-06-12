<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject requests from authenticated-but-disabled accounts and revoke the
 * token they presented.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->is_active === false) {
            $token = $user->currentAccessToken();
            if ($token !== null && method_exists($token, 'delete')) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Your account has been disabled. Please contact an administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
