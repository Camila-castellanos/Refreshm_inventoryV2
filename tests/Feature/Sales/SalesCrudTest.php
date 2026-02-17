<?php

namespace Tests\Feature\Sales;

use App\Models\Sale;
use Tests\TestCaseWithCompany;

class SalesCrudTest extends TestCaseWithCompany
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

        $response = $this->actingAs($this->owner)
            ->post('/inventory/sales', $saleData);

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

        $response = $this->actingAs($this->owner)
            ->post('/inventory/sales', $saleData);

        $response->assertStatus(201);
    }

    public function test_sale_with_credit(): void
    {
        $saleData = [
            'newItems' => [
                [
                    'manufacturer' => 'Samsung',
                    'model' => 'Galaxy S24',
                    'colour' => 'White',
                    'grade' => 'B',
                    'cost' => 400.00,
                    'selling_price' => 500.00,
                    'storage_id' => $this->storage->id,
                    'imei' => '123456789012346',
                    'type' => 'device',
                    'profit' => 100.00,
                ],
            ],
            'subtotal' => 500.00,
            'discount' => 0,
            'flatTax' => 65.00,
            'tax' => 13.00,
            'total' => 565.00,
            'amount_paid' => 65.00,
            'balance_remaining' => 500.00,
            'paid' => 1,
            'credit' => 500.00,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->owner)
            ->post('/inventory/sales', $saleData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('sales', [
            'credit' => 500.00,
            'balance_remaining' => 0,
        ]);
    }

    public function test_can_view_report(): void
    {
        Sale::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/report');

        $response->assertStatus(200);
    }

    public function test_can_generate_report_post(): void
    {
        Sale::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/report', [
                'startDate' => now()->subDays(30)->format('Y-m-d'),
                'endDate' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);
    }

    public function test_sale_requires_authentication(): void
    {
        $saleData = [
            'newItems' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
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

        $response = $this->post('/inventory/sales', $saleData);

        $response->assertRedirect('/login');
    }

    public function test_report_requires_authentication(): void
    {
        $response = $this->get('/inventory/report');

        $response->assertRedirect('/login');
    }
}
