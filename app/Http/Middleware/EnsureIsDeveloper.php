<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsDeveloper
{
    /**
     * Handle an incoming request. Aborts with 403 unless the authenticated
     * user has platform-wide developer access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isDeveloper(), 403);

        return $next($request);
    }
}
