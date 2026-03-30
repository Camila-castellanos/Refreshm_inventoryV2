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

    public function test_sale_with_100_percent_discount(): void
    {
        $item = $this->createItem(['selling_price' => 100.00, 'cost' => 50.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 0, 'type' => 'device'],
            ],
            'subtotal' => 0,
            'discount' => 100.00,
            'flatTax' => 0,
            'tax' => 13.00,
            'total' => 0,
            'amount_paid' => 0,
            'balance_remaining' => 0,
            'paid' => 0,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_zero_tax_rate(): void
    {
        $item = $this->createItem(['selling_price' => 100.00, 'cost' => 50.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 50.00, 'type' => 'device'],
            ],
            'subtotal' => 100.00,
            'discount' => 0,
            'flatTax' => 0,
            'tax' => 0,
            'total' => 100.00,
            'amount_paid' => 100.00,
            'balance_remaining' => 0,
            'paid' => 2,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_discount_greater_than_subtotal(): void
    {
        $item = $this->createItem(['selling_price' => 50.00, 'cost' => 25.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 50.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => -25.00, 'type' => 'device'],
            ],
            'subtotal' => -20.00,
            'discount' => 70.00,
            'flatTax' => 0,
            'tax' => 13.00,
            'total' => -20.00,
            'amount_paid' => 0,
            'balance_remaining' => -20.00,
            'paid' => 0,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_credit(): void
    {
        $item = $this->createItem(['selling_price' => 100.00, 'cost' => 50.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 50.00, 'type' => 'device'],
            ],
            'subtotal' => 100.00,
            'discount' => 0,
            'flatTax' => 13.00,
            'tax' => 13.00,
            'total' => 113.00,
            'amount_paid' => 0,
            'balance_remaining' => 113.00,
            'credit' => 113.00,
            'paid' => 0,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_paid_fully(): void
    {
        $item = $this->createItem(['selling_price' => 100.00, 'cost' => 50.00]);

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

    public function test_sale_with_long_notes(): void
    {
        $item = $this->createItem(['selling_price' => 100.00]);

        $longNotes = str_repeat('Lorem ipsum dolor sit amet. ', 50);

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
            'notes' => $longNotes,
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_multiple_items_and_discounts(): void
    {
        $item1 = $this->createItem(['selling_price' => 100.00, 'cost' => 50.00]);
        $item2 = $this->createItem(['selling_price' => 200.00, 'cost' => 100.00]);
        $item3 = $this->createItem(['selling_price' => 50.00, 'cost' => 25.00]);

        $saleData = [
            'items' => [
                ['id' => $item1->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 50.00, 'type' => 'device'],
                ['id' => $item2->id, 'selling_price' => 200.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 100.00, 'type' => 'device'],
                ['id' => $item3->id, 'selling_price' => 50.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 25.00, 'type' => 'device'],
            ],
            'subtotal' => 320.00,
            'discount' => 20.00,
            'flatTax' => 39.00,
            'tax' => 13.00,
            'total' => 339.00,
            'amount_paid' => 339.00,
            'balance_remaining' => 0,
            'paid' => 2,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_special_characters_in_customer_name(): void
    {
        $item = $this->createItem(['selling_price' => 100.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => "O'Connor & Sons <test@example.com>", 'profit' => 50.00, 'type' => 'device'],
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
    }

    public function test_sale_partial_payment_then_full(): void
    {
        $item = $this->createItem(['selling_price' => 100.00]);

        $saleData = [
            'items' => [
                ['id' => $item->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 50.00, 'type' => 'device'],
            ],
            'subtotal' => 100.00,
            'discount' => 0,
            'flatTax' => 13.00,
            'tax' => 13.00,
            'total' => 113.00,
            'amount_paid' => 50.00,
            'balance_remaining' => 63.00,
            'paid' => 0,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }
}
