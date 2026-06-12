<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the request locale (IT/EN) so API messages and validation errors
 * come back in the caller's language.
 *
 * Precedence: explicit ?lang / X-Locale header, then the Accept-Language
 * header, then the application default. Only the supported locales are
 * honoured; anything else falls back to the configured default.
 */
class SetLocale
{
    private const SUPPORTED = ['en', 'it'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        if ($locale !== null) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    private function resolve(Request $request): ?string
    {
        $explicit = $request->query('lang') ?? $request->header('X-Locale');
        if (is_string($explicit) && $this->isSupported($explicit)) {
            return strtolower(substr($explicit, 0, 2));
        }

        $accept = $request->header('Accept-Language');
        if (is_string($accept) && $accept !== '') {
            foreach (explode(',', $accept) as $part) {
                $tag = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
                if (in_array($tag, self::SUPPORTED, true)) {
                    return $tag;
                }
            }
        }

        return null;
    }

    private function isSupported(string $value): bool
    {
        return in_array(strtolower(substr($value, 0, 2)), self::SUPPORTED, true);
    }
}
