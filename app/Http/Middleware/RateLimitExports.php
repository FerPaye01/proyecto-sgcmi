<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitExports
{
    public function __construct(
        private readonly RateLimiter $limiter
    ) {
    }

    /**
     * Handle an incoming request.
     * Limits exports to 5 per minute per user as per steering rules
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401, 'No autenticado');
        }

        $key = 'export:' . $user->id;
        
        if ($this->limiter->tooManyAttempts($key, 5)) {
            $seconds = $this->limiter->availableIn($key);
            
            abort(429, "Demasiadas exportaciones. Intente nuevamente en {$seconds} segundos.");
        }

        $this->limiter->hit($key, 60); // 60 seconds = 1 minute

        return $next($request);
    }
}

