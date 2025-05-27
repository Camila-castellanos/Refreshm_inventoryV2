<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAuth
{
    public function handle(Request $request, Closure $next, $guard = 'sanctum')
    {
        if (! Auth::guard($guard)->check()) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'You must send a valid token in the Authorization: Bearer <token>'
            ], 401);
        }

        // If authenticated, continue
        return $next($request);
    }
}