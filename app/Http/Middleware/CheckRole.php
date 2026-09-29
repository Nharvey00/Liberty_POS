<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|int  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized access.');
        }

        // Check against role_id or role_name
        $userRoleId = (string) $user->role_id;
        $userRoleName = $user->role ? strtolower($user->role->role_name) : null;

        $roleMap = [
            '1' => ['1', 'level 1', 'cashier'],
            '2' => ['2', 'level 2', 'manager'],
            '3' => ['3', 'level 3', 'owner', 'admin'],
        ];

        $allowed = false;

        foreach ($roles as $role) {
            $roleClean = strtolower(trim((string)$role));

            // Direct role_id match
            if ($userRoleId === $roleClean) {
                $allowed = true;
                break;
            }

            // Direct role_name match
            if ($userRoleName && $userRoleName === $roleClean) {
                $allowed = true;
                break;
            }

            // Mapped match (e.g. role:1 matches 'cashier' or 'Level 1')
            if (isset($roleMap[$userRoleId]) && in_array($roleClean, $roleMap[$userRoleId])) {
                $allowed = true;
                break;
            }
        }

        if (! $allowed) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
