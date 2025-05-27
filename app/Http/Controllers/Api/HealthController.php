<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Endpoint de health check.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status'    => 'API is healthy!',
            'app'       => "Hello from SwiftStock API",
            'timestamp' => now()->toDateTimeString(),
        ], 200);
    }
}