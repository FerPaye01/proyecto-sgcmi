<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401, 'No autenticado');
        }
        
        // ADMIN tiene acceso a todo
        if ($user->hasRole('ADMIN')) {
            return $next($request);
        }
        
        if (!$user->hasPermission($permission)) {
            abort(403, 'No tiene permiso para esta acción');
        }
        
        return $next($request);
    }
}
