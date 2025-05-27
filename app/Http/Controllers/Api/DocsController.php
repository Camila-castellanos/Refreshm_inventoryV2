<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DocsController extends Controller
{
    /**
     * Show basic API documentation.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'endpoints' => [
                'GET /api' => 'This documentation',
                'GET /api/health' => 'Health check',
                'POST /api/login' => [
                    'description' => 'Obtain bearer token',
                    'body' => [
                        'email'    => 'string|required',
                        'password' => 'string|required',
                    ]
                ],
                'Items resource (protected by Bearer Token)' => [
                    'GET    /api/items'           => [
                        'description' => 'List all items',
                        'authentication' => 'Bearer token required',
                        'options'        => [
            // Filters
            'filter[type]'           => 'Exact match on item type',
            'filter[supplier]'       => 'Partial match on supplier',
            'filter[manufacturer]'   => 'Partial match on manufacturer',
            'filter[model]'          => 'Partial match on model',
            'filter[colour]'         => 'Partial match on colour',
            'filter[battery]'        => 'Partial match on battery',
            'filter[grade]'          => 'Exact match on grade',
            'filter[issues]'         => 'Partial match on issues',
            'filter[cost]'           => 'Exact match on cost',
            'filter[imei]'           => 'Exact match on IMEI',
            'filter[selling_price]'  => 'Exact match on selling price',
            'filter[customer]'       => 'Partial match on customer',
            'filter[sold]'           => 'Filter by sold status: 1|true = sold, 0|false = unsold',
            'filter[hold]'           => 'Filter by hold status: 1|true = on hold, 0|false = not on hold',

            // Field selection & pagination
            'fields'   => 'Comma-separated list of fields to include. Allowed fields: id, type, supplier, manufacturer, model, colour, battery, grade, issues, cost, imei, selling_price, customer, discount, tax, subtotal, profit, sold, hold, created_at',
            'per_page' => 'Number of items per page (pagination), default 15',
        ],
                    ],
                ],
            ],
            'authentication' => [
                'Use header' => 'Authorization: Bearer {token}',
                'Obtain token via' => 'POST /api/login',
            ],
        ], 200);
    }
}