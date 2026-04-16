<?php

namespace App\Services\Inventory;

use App\Models\IncomingRequestItem;
use App\Models\Item;

class IncomingRequestEligibilityService
{
    /**
     * @return array{eligible: bool, reason: string|null, item: Item|null}
     */
    public function evaluate(IncomingRequestItem $requestItem): array
    {
        if (! $requestItem->original_item_id) {
            return [
                'eligible' => false,
                'reason' => 'missing_original_item',
                'item' => null,
            ];
        }

        $originalItem = Item::withoutGlobalScopes()
            ->lockForUpdate()
            ->find($requestItem->original_item_id);

        if (! $originalItem) {
            return [
                'eligible' => false,
                'reason' => 'missing_original_item',
                'item' => null,
            ];
        }

        if (! is_null($originalItem->sold) || ! is_null($originalItem->sale_id)) {
            return [
                'eligible' => false,
                'reason' => 'already_sold',
                'item' => $originalItem,
            ];
        }

        if (! is_null($originalItem->hold)) {
            return [
                'eligible' => false,
                'reason' => 'on_hold',
                'item' => $originalItem,
            ];
        }

        if (! is_null($originalItem->storage_id)) {
            $visibleItem = Item::query()->find($requestItem->original_item_id);
            if (! $visibleItem) {
                return [
                    'eligible' => false,
                    'reason' => 'not_visible',
                    'item' => $originalItem,
                ];
            }
        }

        if (is_null($originalItem->storage_id)) {
            return [
                'eligible' => false,
                'reason' => 'unavailable',
                'item' => $originalItem,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'item' => $originalItem,
        ];
    }
}
