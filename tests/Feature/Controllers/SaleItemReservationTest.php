<?php

namespace Tests\Feature\Controllers;

use App\Models\Customer;
use App\Models\Item;
use Carbon\Carbon;
use Tests\TestCaseWithCompany;

class SaleItemReservationTest extends TestCaseWithCompany
{
    /** @test */
    public function it_marks_items_as_reserved_and_keeps_position_on_unpaid_sale(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();
        $item = $this->createItem([
            'storage_id' => $this->storage->id,
            'position' => 10,
        ]);

        $payload = [
            'items' => [
                [
                    'id' => $item->id,
                    'type' => 'device',
                    'customer' => $customer->id,
                    'selling_price' => 500,
                    'cost' => 300,
                    'sold' => Carbon::now()->format('Y-m-d H:i:s'),
                    'profit' => 200,
                ]
            ],
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'flatTax' => 0,
            'total' => 500,
            'balance_remaining' => 500,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'amount_paid' => 0,
            'paid' => 0,
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $item->refresh();

        $this->assertEquals('reserved', $item->status);
        $this->assertEquals($this->storage->id, $item->storage_id);
        $this->assertEquals(10, $item->position);
    }

    /** @test */
    public function it_marks_items_as_sold_and_clears_position_on_paid_sale(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();
        $item = $this->createItem([
            'storage_id' => $this->storage->id,
            'position' => 10,
        ]);

        $payload = [
            'items' => [
                [
                    'id' => $item->id,
                    'type' => 'device',
                    'customer' => $customer->id,
                    'selling_price' => 500,
                    'cost' => 300,
                    'sold' => Carbon::now()->format('Y-m-d H:i:s'),
                    'profit' => 200,
                ]
            ],
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'flatTax' => 0,
            'total' => 500,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'amount_paid' => 500,
            'paid' => 1,
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $item->refresh();

        $this->assertEquals('sold', $item->status);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }

    /** @test */
    public function it_transitions_reserved_item_to_sold_when_sale_is_paid_on_update(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();
        
        $item = $this->createItem([
            'status' => 'reserved',
            'storage_id' => $this->storage->id,
            'position' => 10,
        ]);

        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 500,
            'total' => 500,
        ]);
        $item->update(['sale_id' => $sale->id]);

        $payload = [
            'id' => $sale->id,
            'items' => [
                [
                    'id' => $item->id,
                    'selling_price' => 500,
                    'cost' => 300,
                    'customer' => $customer->id,
                    'sold' => Carbon::now()->format('Y-m-d H:i:s'),
                    'profit' => 200,
                    'type' => 'device',
                ]
            ],
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'total' => 500,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'date' => Carbon::now()->format('Y-m-d'),
            'customer' => $customer->customer,
            'paid' => 1,
            'amount_paid' => 500,
        ];

        $response = $this->post(route('sales.update'), $payload);

        $response->assertStatus(201);
        $item->refresh();

        $this->assertEquals('sold', $item->status);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }

    /** @test */
    public function it_sets_correct_status_for_new_items_on_store(): void
    {
        $this->loginAsOwner();
        
        // Test Unpaid
        $payloadUnpaid = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'New iPhone',
                    'selling_price' => 1000,
                    'profit' => 200,
                ]
            ],
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'paid' => 0,
            'balance_remaining' => 1000,
            'amount_paid' => 0,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('sales.store'), $payloadUnpaid);
        $itemUnpaid = Item::where('model', 'New iPhone')->first();
        $this->assertEquals('reserved', $itemUnpaid->status);

        // Test Paid
        $payloadPaid = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'Other iPhone',
                    'selling_price' => 1000,
                    'profit' => 200,
                ]
            ],
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 1000,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->post(route('sales.store'), $payloadPaid);
        $itemPaid = Item::where('model', 'Other iPhone')->first();
        $this->assertEquals('sold', $itemPaid->status);
    }
}
