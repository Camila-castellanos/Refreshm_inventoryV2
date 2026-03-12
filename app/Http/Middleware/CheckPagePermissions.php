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
    public function handle(Request $request, Closure $next, string $page, ?string $tab = null): Response
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

        // If permissions are null (not set in DB), use defaults from config based on role
        if (is_null($permissions)) {
            $configKey = ($user->role === 'ADMIN' || $user->role === 'OWNER') ? 'permissions.defaults' : 'permissions.user_defaults';
            $permissions = config($configKey);
        }

        // If it's a string (e.g. from DB), decode it
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        // If it's still null or not an array, default to empty array
        if (! is_array($permissions)) {
            $permissions = [];
        }

        // Backward compatibility: If the array is flat (e.g., ["Inventory", "Dashboard"]), convert to object
        // We can check if the first key is numeric, or if array_is_list
        if (! empty($permissions) && array_keys($permissions) === range(0, count($permissions) - 1)) {
            $newPermissions = [];
            foreach ($permissions as $p) {
                if ($p === 'Inventory') {
                    $newPermissions[$p] = ['Active Inventory', 'On Hold', 'Sold'];
                } else {
                    $newPermissions[$p] = [];
                }
            }
            $permissions = $newPermissions;
        }

        // Check page permission
        if (! array_key_exists($page, $permissions)) {
            abort(403, 'Unauthorized access to this page.');
        }

        // Check tab permission if requested
        if ($tab !== null) {
            $allowedTabs = $permissions[$page] ?? [];
            if (! in_array($tab, $allowedTabs)) {
                abort(403, 'Unauthorized access to this tab.');
            }
        }

        return $next($request);
    }
}
