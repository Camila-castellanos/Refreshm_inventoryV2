<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el usuario tiene uno de los roles permitidos
        if (!in_array($user->role, $roles)) {
            // Redirigir a una página de error o a la página anterior
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        return $next($request);
    }
}