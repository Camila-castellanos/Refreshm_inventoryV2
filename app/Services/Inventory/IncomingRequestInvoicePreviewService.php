<?php

namespace App\Services\Inventory;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;

class IncomingRequestInvoicePreviewService
{
    private const MAX_PREVIEW_ITEMS = 4;

    /**
     * @return array<string, mixed>
     */
    public function build(Sale $sale): array
    {
        $baseQuery = Item::query()
            ->where('sale_id', $sale->id)
            ->whereNotNull('sold');

        $totalItems = (clone $baseQuery)->count();

        $items = (clone $baseQuery)
            ->orderByDesc('sold')
            ->orderByDesc('id')
            ->limit(self::MAX_PREVIEW_ITEMS)
            ->get();

        return [
            'sale_id' => $sale->id,
            'customer' => $this->resolveCustomerName($sale),
            'date' => $sale->date?->format('Y-m-d'),
            'paid_status' => ((int) $sale->paid) === 1 || ((int) $sale->paid) === 2 ? 'Paid' : 'Unpaid',
            'total_items' => $totalItems,
            'items_preview' => $items->map(fn (Item $item): array => $this->mapPreviewItem($item))->values()->all(),
        ];
    }

    private function resolveCustomerName(Sale $sale): string
    {
        $firstSoldItem = Item::query()
            ->where('sale_id', $sale->id)
            ->whereNotNull('sold')
            ->orderByDesc('sold')
            ->orderByDesc('id')
            ->first();

        if (! $firstSoldItem) {
            return 'Unknown customer';
        }

        $rawCustomer = $firstSoldItem->customer;

        if (is_numeric($rawCustomer)) {
            $customer = Customer::query()->find((int) $rawCustomer);

            return $customer?->customer ?: (string) $rawCustomer;
        }

        return filled($rawCustomer) ? (string) $rawCustomer : 'Unknown customer';
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPreviewItem(Item $item): array
    {
        $hasLabelData = filled($item->model) || filled($item->manufacturer);

        return [
            'item_id' => $item->id,
            'label' => $hasLabelData ? trim((string) implode(' ', array_filter([$item->manufacturer, $item->model]))) : 'Deleted item',
            'type' => filled($item->type) ? $item->type : 'unknown',
            'identifier' => filled($item->imei) ? $item->imei : 'N/A',
            'selling_price' => (float) ($item->selling_price ?? 0),
            'currency' => 'CAD',
        ];
    }
}
