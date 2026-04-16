<?php

namespace Tests\Feature\Inventory;

use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Item;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class IncomingRequestAppendConcurrencyTest extends TestCaseWithCompany
{
    public function test_append_replay_with_same_idempotency_key_returns_stored_result_without_duplicates(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'subtotal' => 100,
            'total' => 100,
            'amount_paid' => 0,
            'balance_remaining' => 100,
            'tax' => 0,
            'flatTax' => 0,
            'discount' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'customer' => 'Invoice Customer',
            'selling_price' => 100,
            'cost' => 50,
        ]);

        $appendableItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'selling_price' => 80,
            'cost' => 40,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $appendableItem->id,
            'selling_price' => 80,
            'cost' => 40,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $first = $this->actingAs($this->owner)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'same-key-1',
            ]);

        $first->assertOk()
            ->assertJsonPath('counts.appended', 1)
            ->assertJsonPath('idempotent_replay', false);

        $second = $this->actingAs($this->owner)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'same-key-1',
            ]);

        $second->assertOk()
            ->assertJsonPath('counts.appended', 1)
            ->assertJsonPath('idempotent_replay', true);

        $appendableItem->refresh();

        $this->assertSame($sale->id, $appendableItem->sale_id);
        $this->assertDatabaseCount('incoming_request_append_attempts', 1);
    }

    public function test_append_returns_409_when_request_already_processed(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'subtotal' => 100,
            'total' => 100,
            'amount_paid' => 0,
            'balance_remaining' => 100,
            'tax' => 0,
            'flatTax' => 0,
            'discount' => 0,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'stale-processed-1',
            ]);

        $response->assertStatus(409);
    }
}
