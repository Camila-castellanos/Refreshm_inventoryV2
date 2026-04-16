<?php

namespace App\Actions\Sales;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Storage;
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
        $finalTotal = (float) $sale->total;
        $finalFlatTax = (float) $sale->flatTax;
        $finalSubTotal = (float) $sale->subtotal;

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

        foreach ($items as $item) {
            $itemData = Item::findOrFail($item['id']);

            Item::where('id', $item['id'])->update([
                'sold' => $saleDate ?? Carbon::now(),
                'selling_price' => $item['selling_price'],
                'customer' => $customer,
                'profit' => $item['selling_price'] - $itemData->cost,
                'sale_id' => $sale->id,
                'sold_position' => $item['position'] ?? null,
                'sold_storage_id' => $item['storage_id'] ?? null,
                'sold_storage_name' => isset($item['storage_id']) ? Storage::find($item['storage_id'])?->name : null,
                'position' => null,
                'storage_id' => null,
            ]);

            $subtotal = $item['selling_price'] - $discount;
            $flatTax = $subtotal * $tax;
            $total = $subtotal + $flatTax;
            $finalSubTotal += $subtotal;
            $finalFlatTax += $flatTax;
            $finalTotal += $total;
        }

        $balance = $finalTotal - $sale->amount_paid;
        $paid = 0;
        if ($sale->amount_paid >= $finalTotal) {
            $paid = 1;
            $balance = 0;
        }

        Sale::where('id', $sale->id)->update([
            'flatTax' => $finalFlatTax,
            'subtotal' => $finalSubTotal,
            'total' => $finalTotal,
            'balance_remaining' => $balance,
            'paid' => $paid,
        ]);
    }
}
