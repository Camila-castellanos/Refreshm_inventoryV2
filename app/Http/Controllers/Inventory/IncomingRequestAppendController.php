<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AppendIncomingRequestToInvoiceRequest;
use App\Models\IncomingRequest;
use App\Models\Sale;
use App\Services\Inventory\AppendIncomingRequestToInvoiceService;
use App\Services\Inventory\Exceptions\AppendIncomingRequestConflictException;
use App\Services\Inventory\Exceptions\AppendIncomingRequestNoEligibleItemsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class IncomingRequestAppendController extends Controller
{
    public function __construct(
        private readonly AppendIncomingRequestToInvoiceService $appendService,
    ) {}

    public function append(AppendIncomingRequestToInvoiceRequest $request, int $incomingRequest): JsonResponse
    {
        $incomingRequestModel = IncomingRequest::findOrFail($incomingRequest);
        $sale = Sale::findOrFail((int) $request->input('sale_id'));
        $correlationId = $request->header('X-Request-Id')
            ?? $request->header('X-Correlation-Id')
            ?? (string) $request->input('idempotency_key');

        try {
            $result = $this->appendService->execute(
                incomingRequest: $incomingRequestModel,
                sale: $sale,
                idempotencyKey: (string) $request->input('idempotency_key'),
                actorId: $request->user()?->id,
            );

            Log::info('incoming_request.append_to_invoice.attempt', [
                'correlation_id' => $correlationId,
                'request_id' => $incomingRequestModel->id,
                'sale_id' => $sale->id,
                'appended_count' => (int) ($result['counts']['appended'] ?? 0),
                'skipped_count' => (int) ($result['counts']['skipped'] ?? 0),
                'state_transition' => (string) ($result['state_transition'] ?? ''),
                'idempotent_replay' => (bool) ($result['idempotent_replay'] ?? false),
                'outcome' => 'success',
            ]);

            return response()->json($result);
        } catch (AppendIncomingRequestNoEligibleItemsException $exception) {
            Log::warning('incoming_request.append_to_invoice.failure', [
                'correlation_id' => $correlationId,
                'request_id' => $incomingRequestModel->id,
                'sale_id' => $sale->id,
                'status_code' => 422,
                'error_class' => class_basename($exception),
                'outcome' => 'failure',
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (AppendIncomingRequestConflictException $exception) {
            Log::warning('incoming_request.append_to_invoice.failure', [
                'correlation_id' => $correlationId,
                'request_id' => $incomingRequestModel->id,
                'sale_id' => $sale->id,
                'status_code' => 409,
                'error_class' => class_basename($exception),
                'outcome' => 'failure',
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }
}
