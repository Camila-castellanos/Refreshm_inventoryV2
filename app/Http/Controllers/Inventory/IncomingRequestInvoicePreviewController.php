<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Inventory\IncomingRequestInvoicePreviewService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class IncomingRequestInvoicePreviewController extends Controller
{
    public function __construct(
        private readonly IncomingRequestInvoicePreviewService $previewService,
    ) {}

    public function show(int $sale): JsonResponse
    {
        try {
            $saleModel = Sale::query()->findOrFail($sale);

            return response()->json($this->previewService->build($saleModel));
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Invoice not found.',
            ], 404);
        } catch (Throwable $exception) {
            Log::warning('incoming_request.invoice_preview.failure', [
                'sale_id' => $sale,
                'error_class' => class_basename($exception),
                'status_code' => 500,
                'outcome' => 'failure',
            ]);

            return response()->json([
                'message' => 'Unable to load invoice preview right now.',
            ], 500);
        }
    }
}
