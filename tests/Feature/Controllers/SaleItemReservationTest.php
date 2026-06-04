<?php

namespace Tests\Feature\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Storage;
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

    /** @test */
    public function test_update_paid_sale_with_new_item_assigns_storage(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();

        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 1000,
            'total' => 1000,
        ]);

        $payload = [
            'id' => $sale->id,
            'items' => [],
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'Brand New Device',
                    'selling_price' => 1000,
                    'imei' => '123456789012345',
                    'issues' => '',
                ],
            ],
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'date' => Carbon::now()->format('Y-m-d'),
            'customer' => $customer->customer,
            'paid' => 1,
            'amount_paid' => 1000,
        ];

        $response = $this->post(route('sales.update'), $payload);

        $response->assertStatus(201);

        $item = Item::where('model', 'Brand New Device')->first();
        $this->assertNotNull($item, 'Expected new item to be created by SaleController::update.');
        $this->assertEquals('sold', $item->status);

        // Active fields nulled by saving hook after snapshot.
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);

        // Snapshot fields populated from the auto-assigned storage.
        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertNotNull($item->sold_position);
        $this->assertEquals($this->storage->name, $item->sold_storage_name);
    }

    /** @test */
    public function test_update_paid_sale_existing_item_populates_snapshot(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();

        $item = $this->createItem([
            'status' => 'reserved',
            'storage_id' => $this->storage->id,
            'position' => 7,
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
                ],
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

        // Active fields nulled by the update + saving hook.
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);

        // Snapshot fields populated from the pre-update in-memory state.
        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertEquals(7, $item->sold_position);
        $this->assertEquals($this->storage->name, $item->sold_storage_name);
    }

    /** @test */
    public function test_update_paid_sale_resold_item_preserves_snapshot(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();

        // Item is already SOLD with a snapshot from a prior sale.
        $item = $this->createItem([
            'status' => 'sold',
            'sold' => Carbon::now()->subDay(),
            'storage_id' => null,
            'position' => null,
            'sold_storage_id' => $this->storage->id,
            'sold_position' => 3,
            'sold_storage_name' => $this->storage->name,
        ]);

        $sale = \App\Models\Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 1,
            'balance_remaining' => 0,
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
                ],
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

        // Snapshot must NOT be clobbered with the current null active fields.
        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertEquals(3, $item->sold_position);
        $this->assertEquals($this->storage->name, $item->sold_storage_name);
    }

    /** @test */
    public function test_showReport_includes_reserved_items_with_current_storage(): void
    {
        $this->loginAsOwner();

        // Dedicated storage with a name we can assert on.
        $shelfZ = Storage::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Shelf Z',
            'limit' => 20,
        ]);

        // RESERVED item with a real, current storage.
        $item = $this->createItem([
            'status' => 'reserved',
            'storage_id' => $shelfZ->id,
            'position' => 5,
        ]);

        // Unpaid sale created within the default 7-day window.
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 500,
            'total' => 500,
            'created_at' => Carbon::now()->subDay(),
        ]);
        $item->update(['sale_id' => $sale->id]);

        // Hit the report endpoint (no date params -> default last-7-days window).
        $response = $this->get(route('sales.report'));

        $response->assertStatus(200);

        $items = $response->original->getData()['page']['props']['items'] ?? null;
        $this->assertIsArray($items, 'Expected showReport to render with an items array.');

        $found = collect($items)->firstWhere('id', $item->id);
        $this->assertNotNull($found, "Expected item {$item->id} to appear in the Sold report.");

        // Snapshot is empty for RESERVED items.
        $this->assertNull($found['sold_storage_name']);
        $this->assertNull($found['sold_storage_id']);
        $this->assertNull($found['sold_position']);

        // Current storage fields ARE present (the Option A fallback payload).
        $this->assertEquals($shelfZ->id, $found['storage_id']);
        $this->assertEquals(5, $found['position']);
        $this->assertIsArray($found['storage']);
        $this->assertEquals($shelfZ->id, $found['storage']['id']);
        $this->assertEquals('Shelf Z', $found['storage']['name']);
        $this->assertEquals(20, $found['storage']['limit']);
    }
}
