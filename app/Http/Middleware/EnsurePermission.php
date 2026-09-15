<?php

namespace App\Http\Middleware;

use App\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request. Aborts with 403 unless the authenticated
     * user is the store owner or has been granted the given permission.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasPermission(Permission::from($permission)),
            403
        );

        return $next($request);
    }
}
