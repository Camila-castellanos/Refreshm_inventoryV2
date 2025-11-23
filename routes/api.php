<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\ExchangeRateController;
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

// Exchange rate endpoints
Route::prefix('exchange-rate')->group(function () {
    Route::get('/', [ExchangeRateController::class, 'getExchangeRate'])->name('exchange-rate.get');
    Route::post('/refresh', [ExchangeRateController::class, 'refreshExchangeRate'])->name('exchange-rate.refresh');
    Route::delete('/cache', [ExchangeRateController::class, 'clearCache'])->name('exchange-rate.clear-cache');
});

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