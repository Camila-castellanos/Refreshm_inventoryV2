<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemController;
use App\Http\Middleware\ApiAuth;
use App\Http\Controllers\Api\DocsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use Illuminate\Http\JsonResponse;

Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->name('csrf-token')->middleware('web');

Route::get('health', [\App\Http\Controllers\Api\HealthController::class, 'index']);
Route::get('/', [DocsController::class, 'index']);
Route::get('login', function (Request $request) {
    return response()->json([
        'error'   => 'Method not allowed',
        'message' => 'This endpoint only accepts POST requests for authentication.'
    ], 405);
});
Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
// final api implementation with authentication middleware
// Route::middleware(ApiAuth::class)->group(function () {
//     Route::apiResource('items', ItemController::class);
// });

// temporary implementation without authentication middleware
Route::apiResource('items', ItemController::class); 