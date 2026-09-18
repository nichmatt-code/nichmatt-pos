<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreHasAccess
{
    /**
     * Redirect to the billing page unless the authenticated user's store
     * is still within its free trial or has an active paid subscription.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->store && ! $user->store->hasAccess()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Masa trial/langganan toko sudah berakhir.'], 402);
            }

            return redirect()->route('billing.subscribe');
        }

        return $next($request);
    }
}
