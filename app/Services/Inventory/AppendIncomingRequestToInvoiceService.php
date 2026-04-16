<?php

namespace App\Services\Inventory;

use App\Actions\Sales\AppendItemsToSaleAction;
use App\Models\IncomingRequest;
use App\Models\IncomingRequestAppendAttempt;
use App\Models\Sale;
use App\Services\Inventory\Exceptions\AppendIncomingRequestConflictException;
use App\Services\Inventory\Exceptions\AppendIncomingRequestNoEligibleItemsException;
use Illuminate\Support\Facades\DB;

class AppendIncomingRequestToInvoiceService
{
    public const ZERO_ELIGIBLE_MESSAGE = 'No items from this request can be added to the selected invoice because they are no longer eligible (already sold, unavailable, or not visible).';

    public function __construct(
        private readonly IncomingRequestEligibilityService $eligibilityService,
        private readonly AppendItemsToSaleAction $appendItemsToSaleAction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(IncomingRequest $incomingRequest, Sale $sale, string $idempotencyKey, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($incomingRequest, $sale, $idempotencyKey, $actorId) {
            $lockedRequest = IncomingRequest::where('id', $incomingRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $attempt = IncomingRequestAppendAttempt::where('incoming_request_id', $lockedRequest->id)
                ->where('sale_id', $sale->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($attempt) {
                $payload = $attempt->response_payload;
                $payload['idempotent_replay'] = true;

                return $payload;
            }

            if ($lockedRequest->processed) {
                throw new AppendIncomingRequestConflictException('Incoming request has already been processed.');
            }

            $requestItems = $lockedRequest->items()->lockForUpdate()->get();

            $eligibleForAppend = [];
            $skippedItems = [];

            foreach ($requestItems as $requestItem) {
                $eligibility = $this->eligibilityService->evaluate($requestItem);

                if (! $eligibility['eligible']) {
                    $skippedItems[] = [
                        'incoming_request_item_id' => $requestItem->id,
                        'reason' => $eligibility['reason'],
                    ];

                    continue;
                }

                $eligibleForAppend[] = [
                    'incoming_request_item_id' => $requestItem->id,
                    'id' => $eligibility['item']->id,
                    'selling_price' => (float) ($requestItem->selling_price ?? $eligibility['item']->selling_price ?? 0),
                    'position' => $eligibility['item']->position,
                    'storage_id' => $eligibility['item']->storage_id,
                ];
            }

            if (count($eligibleForAppend) === 0) {
                throw new AppendIncomingRequestNoEligibleItemsException(self::ZERO_ELIGIBLE_MESSAGE);
            }

            $this->appendItemsToSaleAction->execute($sale, $eligibleForAppend);

            $appendedIncomingRequestItemIds = collect($eligibleForAppend)
                ->pluck('incoming_request_item_id')
                ->values()
                ->all();

            $lockedRequest->items()
                ->whereIn('id', $appendedIncomingRequestItemIds)
                ->delete();

            $pendingCount = $lockedRequest->items()->count();
            $appendedCount = count($eligibleForAppend);
            $skippedCount = count($skippedItems);

            $stateTransition = $pendingCount === 0 ? 'full' : 'partial';

            if ($pendingCount === 0) {
                $lockedRequest->processed = true;
                $lockedRequest->save();
            }

            $payload = [
                'request_id' => $lockedRequest->id,
                'sale_id' => $sale->id,
                'appended_items' => collect($eligibleForAppend)->map(fn ($item) => [
                    'incoming_request_item_id' => $item['incoming_request_item_id'],
                    'item_id' => $item['id'],
                ])->values()->all(),
                'skipped_items' => $skippedItems,
                'counts' => [
                    'appended' => $appendedCount,
                    'skipped' => $skippedCount,
                    'pending' => $pendingCount,
                ],
                'state_transition' => $stateTransition,
                'idempotent_replay' => false,
                'warnings' => ['shipping_not_appended'],
            ];

            IncomingRequestAppendAttempt::create([
                'incoming_request_id' => $lockedRequest->id,
                'sale_id' => $sale->id,
                'idempotency_key' => $idempotencyKey,
                'user_id' => $actorId,
                'state_transition' => $stateTransition,
                'appended_count' => $appendedCount,
                'skipped_count' => $skippedCount,
                'response_payload' => $payload,
            ]);

            return $payload;
        });
    }
}
