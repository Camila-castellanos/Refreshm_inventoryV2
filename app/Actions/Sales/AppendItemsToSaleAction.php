<?php

namespace App\Actions\Sales;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use Carbon\Carbon;

class AppendItemsToSaleAction
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function execute(Sale $sale, array $items): void
    {
        $saleDate = $sale->created_at ?: Carbon::now()->format('Y-m-d');
        $tax = intval($sale->tax) / 100;
        $discount = $sale->discount;

        $customer = null;
        foreach ($sale->items as $existingItem) {
            if (! empty($existingItem->customer)) {
                $customer = $existingItem->customer;
                break;
            }
        }

        if (is_numeric($customer)) {
            $customerModel = Customer::find($customer);
            $customer = $customerModel ? $customerModel->customer : $customer;
        }

        // Calculate new totals first so we know if the sale ends up fully paid
        $runningTotal = (float) $sale->total;
        $runningSubtotal = (float) $sale->subtotal;
        $runningFlatTax = (float) $sale->flatTax;

        foreach ($items as $item) {
            $subtotal = $item['selling_price'] - $discount;
            $flatTax = $subtotal * $tax;
            $total = $subtotal + $flatTax;
            $runningSubtotal += $subtotal;
            $runningFlatTax += $flatTax;
            $runningTotal += $total;
        }

        $isFullyPaid = $sale->amount_paid >= $runningTotal;
        $balance = max(0.0, $runningTotal - $sale->amount_paid);

        // Update each item via model save so the boot hook handles:
        // - status: 'sold' (if fully paid) or 'reserved' (if not)
        // - storage_id and position: cleared for sold, kept for reserved
        // - sold_storage_id / sold_position / sold_storage_name: auto-recorded on transition
        foreach ($items as $item) {
            $itemModel = Item::findOrFail($item['id']);

            $itemModel->selling_price = $item['selling_price'];
            $itemModel->customer = $customer;
            $itemModel->profit = $item['selling_price'] - $itemModel->cost;
            $itemModel->sale_id = $sale->id;

            if ($isFullyPaid) {
                $itemModel->sold = $saleDate ?? Carbon::now();
            }

            $itemModel->save();
        }

        Sale::where('id', $sale->id)->update([
            'flatTax' => $runningFlatTax,
            'subtotal' => $runningSubtotal,
            'total' => $runningTotal,
            'balance_remaining' => $balance,
            'paid' => $isFullyPaid ? 1 : 0,
        ]);
    }
}
