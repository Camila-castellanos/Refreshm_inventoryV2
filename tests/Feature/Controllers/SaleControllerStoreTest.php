<?php

namespace Tests\Feature\Controllers;

use App\Models\Item;
use Carbon\Carbon;
use Tests\TestCaseWithCompany;

class SaleControllerStoreTest extends TestCaseWithCompany
{
    /** @test */
    public function test_store_paid_sale_with_new_items_assigns_storage(): void
    {
        $this->loginAsOwner();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'Test iPhone Pro',
                    'selling_price' => 800,
                    'profit' => 200,
                ],
            ],
            'subtotal' => 800,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 800,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);

        $item = Item::where('model', 'Test iPhone Pro')->first();
        $this->assertNotNull($item, 'Expected new item to be persisted by SaleController::store.');

        // Active fields nulled by saving hook after snapshot.
        $this->assertEquals('sold', $item->status);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);

        // Snapshot fields populated from the auto-assigned storage.
        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertNotNull($item->sold_position);
        $this->assertEquals($this->storage->name, $item->sold_storage_name);
    }

    /** @test */
    public function test_store_unpaid_sale_with_new_items_assigns_storage(): void
    {
        $this->loginAsOwner();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'Reserved iPhone',
                    'selling_price' => 600,
                    'profit' => 100,
                ],
            ],
            'subtotal' => 600,
            'discount' => 0,
            'tax' => 0,
            'paid' => 0,
            'balance_remaining' => 600,
            'amount_paid' => 0,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);

        $item = Item::where('model', 'Reserved iPhone')->first();
        $this->assertNotNull($item);
        $this->assertEquals('reserved', $item->status);

        // Unpaid path: storage_id set by controller, position auto-assigned by
        // the Item::creating hook (Item.php:370-377).
        $this->assertEquals($this->storage->id, $item->storage_id);
        $this->assertNotNull($item->position);

        // No snapshot needed yet — item is not SOLD.
        $this->assertNull($item->sold_storage_id);
    }
}
