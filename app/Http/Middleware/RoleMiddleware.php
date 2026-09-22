<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user() || ! $request->user()->role) {
            abort(403, 'Unauthorized access.');
        }

        // Must check against 'role_name' as defined in the database/Model
        if (! in_array($request->user()->role->role_name, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}