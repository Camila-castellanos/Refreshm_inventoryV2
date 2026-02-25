<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPagePermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Owners have all permissions
        if ($user->role === 'OWNER') {
            return $next($request);
        }

        $permissions = $user->page_permissions;

        // If permissions are null (not set in DB), use defaults
        if (is_null($permissions)) {
            $permissions = ['Inventory'];
        }

        // If it's a string (e.g. from DB), decode it
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // If it's still null or not an array, default to empty array
        if (! is_array($permissions)) {
            $permissions = [];
        }

        if (! in_array($permission, $permissions)) {
            abort(403, 'Unauthorized access to this page.');
        }

        return $next($request);
    }
}
