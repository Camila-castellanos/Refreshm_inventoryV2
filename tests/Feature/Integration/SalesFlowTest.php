<?php

namespace Tests\Feature\Integration;

use App\Models\Sale;
use Tests\TestCaseWithCompany;

class SalesFlowTest extends TestCaseWithCompany
{
    public function test_can_create_sale_with_new_items(): void
    {
        $saleData = [
            'newItems' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'colour' => 'Black',
                    'grade' => 'A',
                    'battery' => '90%',
                    'cost' => 500.00,
                    'selling_price' => 699.99,
                    'storage_id' => $this->storage->id,
                    'imei' => '123456789012345',
                    'type' => 'device',
                    'profit' => 199.99,
                ],
            ],
            'subtotal' => 699.99,
            'discount' => 0,
            'flatTax' => 90.99,
            'tax' => 13.00,
            'total' => 790.98,
            'amount_paid' => 790.98,
            'balance_remaining' => 0,
            'paid' => 2,
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)->post('/inventory/sales', $saleData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('sales', [
            'user_id' => $this->owner->id,
        ]);
    }

    public function test_can_create_sale_with_existing_items(): void
    {
        $item = $this->createItem(['selling_price' => 100.00]);

        $saleData = [
            'items' => [
                [
                    'id' => $item->id,
                    'selling_price' => 100.00,
                    'sold' => now()->format('Y-m-d H:i:s'),
                    'customer' => 'Test Customer',
                    'profit' => 50.00,
                    'type' => 'device',
                ],
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

    public function test_sale_with_multiple_items(): void
    {
        $item1 = $this->createItem(['selling_price' => 100.00]);
        $item2 = $this->createItem(['selling_price' => 200.00]);

        $saleData = [
            'items' => [
                ['id' => $item1->id, 'selling_price' => 100.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 50.00, 'type' => 'device'],
                ['id' => $item2->id, 'selling_price' => 200.00, 'sold' => now()->format('Y-m-d H:i:s'), 'customer' => 'Test', 'profit' => 100.00, 'type' => 'device'],
            ],
            'subtotal' => 300.00,
            'discount' => 0,
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

        $sale = Sale::latest('id')->first();
        $this->assertEquals(300.00, $sale->subtotal);
    }

    public function test_sale_marks_items_as_sold(): void
    {
        $item = $this->createItem(['selling_price' => 100.00]);
        $this->assertNull($item->sold);

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
}
