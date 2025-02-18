<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', function() {
    return Inertia::render('Auth/Login');
});

Route::get('/', function () {
    return Inertia::render('Auth/Login', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::prefix('inventory')->group(function () {
    Route::get('/items', function () {
        return Inertia::render('Inventory/Index', [
            'layout' => 'AppLayout', 
        ]);

    });

    Route::get('/vendors', function () { 
        return Inertia::render('Vendors/Vendors', ['layout' => 'AppLayout']); 
    });});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
