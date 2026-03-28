<?php

namespace Tests\Feature\Integration;

use Tests\TestCaseWithCompany;

class SalesEdgeCaseTest extends TestCaseWithCompany
{
    public function test_sale_without_items(): void
    {
        $saleData = [
            'items' => [],
            'subtotal' => 0,
            'discount' => 0,
            'flatTax' => 0,
            'tax' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'balance_remaining' => 0,
            'paid' => 0,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_duplicate_items(): void
    {
        $item = $this->createItem(['imei' => '123456789012345', 'selling_price' => 100.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 50.00, 'type' => 'device'],
            ],
            'subtotal' => 100.00,
            'discount' => 0,
            'flatTax' => 13.00,
            'tax' => 13.00,
            'total' => 113.00,
            'amount_paid' => 113.00,
            'balance_remaining' => 0,
            'paid' => 2,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);

        $item->refresh();
        $this->assertNotNull($item->sold);
    }

    public function test_large_number_of_items_in_sale(): void
    {
        $items = [];
        for ($i = 0; $i < 20; $i++) {
            $items[] = [
                'manufacturer' => 'Apple',
                'model' => 'iPhone 15',
                'colour' => 'Black',
                'grade' => 'A',
                'battery' => '90%',
                'cost' => 500.00,
                'selling_price' => 699.99,
                'storage_id' => $this->storage->id,
                'imei' => '12345678901234'.$i,
                'type' => 'device',
                'profit' => 199.99,
            ];
        }

        $saleData = [
            'newItems' => $items,
            'subtotal' => 13999.80,
            'discount' => 0,
            'flatTax' => 1819.97,
            'tax' => 13.00,
            'total' => 15819.77,
            'amount_paid' => 15819.77,
            'balance_remaining' => 0,
            'paid' => 2,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }
}
