<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     * Set PHP and Laravel timezone based on authenticated user.
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->timezone) {
            $timezone = Auth::user()->timezone;
        }

        if ($timezone) {
            // Hacemos que la zona horaria esté disponible globalmente para esta petición
            // usando el sistema de configuración de Laravel.
            config(['app.user_timezone' => $timezone]);
        }

        return $next($request);
    }
};