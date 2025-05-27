<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
  try {
    // Check if 'fields' parameter is present in the request and set it for query builder format
    if ($request->has('fields')) {
        $request->query->set('fields.items', $request->get('fields'));
    }
    //Define allowed and default fields, and allowed filters 
    $allowedFields = ['id', 'type', 'supplier', 'manufacturer', 'model', 'colour', 'battery', 'grade', 'issues', 'cost', 'imei', 'selling_price', 'customer', 'discount', 'tax', 'subtotal', 'profit', 'created_at'];
    $defaultFields = ['id', 'type', 'model', 'colour', 'battery', 'grade', 'cost', 'imei', 'selling_price'];
    $allowedFilters = [
            AllowedFilter::exact('type'),
            AllowedFilter::partial('supplier'),
            AllowedFilter::partial('manufacturer'),
            AllowedFilter::partial('model'),
            AllowedFilter::partial('colour'),
            AllowedFilter::partial('battery'),
            AllowedFilter::exact('grade'),
            AllowedFilter::partial('issues'),
            AllowedFilter::exact('cost'),
            AllowedFilter::exact('imei'),
            AllowedFilter::exact('selling_price'),
            AllowedFilter::partial('customer'),
            AllowedFilter::callback('sold', function ($query, $value) {
                // filter[sold]=1 o true → whereNotNull('sold')
                // filter[sold]=0 o false → whereNull('sold')
                $v = strtolower((string) $value);
                if ($v === '1' || $v === 'true') {
                    $query->whereNotNull('sold');
                } else {
                    $query->whereNull('sold');
                }
            }),
        ];
    // add to QueryBuilder the allowed fields
    $builder = QueryBuilder::for(Item::class)
        ->allowedFields($allowedFields)
        ->allowedFilters($allowedFilters);

    // select fields based on request or use default
    if (! $request->has('fields.items')) {
        $builder->select($defaultFields);
    }

    //apply pagination and append query parameters
    $items = $builder
        ->paginate($request->get('per_page', 15))
        ->appends($request->query());

    // send response
    return response()->json($items, 200);
    } catch (\Throwable $e) {              // atrapa Exception y Error
        Log::error($e->getMessage());     // vuelca el detalle al log
        return response()->json([
            'error'   => 'No se pudieron obtener los ítems',
            'message' => $e->getMessage()
        ], 500);
    }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        //
    }
}
