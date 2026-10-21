<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role?->name;

        // Super Admin has bypass access (including during active impersonation)
        if ($userRole === 'super_admin' || $request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'Unauthorized. Your role ('.($userRole ?? 'none').') does not have access to this action.');
        }

        return $next($request);
    }
}
