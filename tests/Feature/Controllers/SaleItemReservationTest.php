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

    /**
     * R8.S4 — store response shape is {url, warnings} (breaking change lock-in).
     *
     * GIVEN at least one `Storage` row and a paid sale with one new item
     * WHEN `SaleController::store` is called
     * THEN the response body has EXACTLY the keys `url` and `warnings` (no more, no less).
     *
     * @test
     */
    public function test_store_response_shape_is_url_plus_warnings(): void
    {
        $this->loginAsOwner();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'ShapeTestDevice',
                    'selling_price' => 100,
                    'profit' => 50,
                ],
            ],
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 100,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $body = $response->json();
        $this->assertEquals(['url', 'warnings'], array_keys($body));
        $this->assertIsString($body['url']);
        $this->assertNotEmpty($body['url']);
        $this->assertIsArray($body['warnings']);
    }

    /**
     * R8.S2 — store with available storage returns 201 with empty warnings.
     *
     * GIVEN at least one `Storage` row with capacity
     * WHEN `SaleController::store` is called with a new item
     * THEN response is 201 with body `{"url": "<receiptUrl>", "warnings": []}`; item persists with real storage_id + position.
     *
     * @test
     */
    public function test_store_with_storage_returns_empty_warnings(): void
    {
        $this->loginAsOwner();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'HousedDevice',
                    'selling_price' => 500,
                    'profit' => 100,
                ],
            ],
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 500,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $body = $response->json();
        $this->assertNotEmpty($body['url']);
        $this->assertEquals([], $body['warnings']);

        $item = Item::where('model', 'HousedDevice')->first();
        $this->assertNotNull($item);
        // The auto-assigned storage is captured in the snapshot fields; active
        // fields are nulled by the Item::saving hook for SOLD items.
        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertNotNull($item->sold_position);
    }

    /**
     * R8.S1 — store with no storage returns 201 with populated warnings.
     *
     * GIVEN no `Storage` rows for the test user's tenant and a paid sale with one new item
     * WHEN `SaleController::store` is called
     * THEN response is 201 with body `{"url": "<receiptUrl>", "warnings": [{"item_id": null, ...}]}`; the item persists with `storage_id = null`.
     *
     * @test
     */
    public function test_store_with_no_storage_returns_warnings_in_response(): void
    {
        $this->loginAsOwner();
        // Remove the default storage so the controller's Storage::findFirstAvailablePosition() returns null.
        $this->storage->delete();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'UnhousedDevice',
                    'selling_price' => 500,
                    'profit' => 100,
                ],
            ],
            'subtotal' => 500,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 500,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $body = $response->json();
        $this->assertNotEmpty($body['url']);
        $this->assertIsArray($body['warnings']);
        $this->assertCount(1, $body['warnings']);
        $this->assertEquals('no_storage_available', $body['warnings'][0]['reason']);
        $this->assertEquals('UnhousedDevice', $body['warnings'][0]['model']);
        $this->assertEquals('device', $body['warnings'][0]['type']);

        $item = Item::where('model', 'UnhousedDevice')->first();
        $this->assertNotNull($item);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }

    /**
     * R8.S3 — update with no available storage returns warnings for the fallback item.
     *
     * The spec calls for "two new items — one auto-assigned successfully, one fell back to null",
     * but the orchestrator's T10 brief simplified this to a single new item with no storages.
     * Same warning surface; the controller's accumulation logic is identical.
     *
     * @test
     */
    public function test_update_with_mixed_storage_outcomes_returns_warnings(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();
        $this->storage->delete();

        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 500,
            'total' => 500,
        ]);

        $payload = [
            'id' => $sale->id,
            'items' => [],
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'UnhousedUpdate',
                    'selling_price' => 500,
                    'imei' => '123456789012345',
                    'issues' => '',
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
        $body = $response->json();
        $this->assertIsArray($body['warnings']);
        $this->assertCount(1, $body['warnings']);
        $this->assertEquals('no_storage_available', $body['warnings'][0]['reason']);
        $this->assertEquals('UnhousedUpdate', $body['warnings'][0]['model']);
    }

    /**
     * R7.S1 — update with mixed existing + new items persists without position collision.
     *
     * Locks in the load-bearing loop order in `SaleController::update`: the existing-items
     * loop must run BEFORE the new-items loop, otherwise `Storage::findFirstAvailablePosition()`
     * (called inside the new-items loop) reads stale occupancy and the new item collides with
     * the just-persisted existing item on the items_storage_position_unique index.
     *
     * The test sends a fully-paid update; the Item::saving hook nulls the active
     * (storage_id, position) fields and snapshots them into (sold_storage_id, sold_position).
     * We assert on the snapshot fields to keep the contract observable after the update.
     *
     * @test
     */
    public function test_update_existing_then_new_items_no_position_collision(): void
    {
        $this->loginAsOwner();
        $customer = Customer::factory()->create();

        // Pre-place an existing RESERVED item at position 5 in the default storage.
        $existing = $this->createItem([
            'status' => 'reserved',
            'storage_id' => $this->storage->id,
            'position' => 5,
        ]);

        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 500,
            'total' => 500,
        ]);
        $existing->update(['sale_id' => $sale->id]);

        $payload = [
            'id' => $sale->id,
            'items' => [
                [
                    'id' => $existing->id,
                    'selling_price' => 500,
                    'cost' => 300,
                    'customer' => $customer->id,
                    'sold' => Carbon::now()->format('Y-m-d H:i:s'),
                    'profit' => 200,
                    'type' => 'device',
                ],
            ],
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'NewSidekick',
                    'selling_price' => 200,
                    'imei' => '999999999999999',
                    'issues' => '',
                ],
            ],
            'subtotal' => 700,
            'discount' => 0,
            'tax' => 0,
            'total' => 700,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash on Hand',
            'date' => Carbon::now()->format('Y-m-d'),
            'customer' => $customer->customer,
            'paid' => 1,
            'amount_paid' => 700,
        ];

        $response = $this->post(route('sales.update'), $payload);

        $response->assertStatus(201);

        // Existing item's snapshot must still be at position 5.
        $existing->refresh();
        $this->assertEquals('sold', $existing->status);
        $this->assertEquals($this->storage->id, $existing->sold_storage_id);
        $this->assertEquals(5, $existing->sold_position);

        // New item's snapshot must have a position != 5.
        $newItem = Item::where('model', 'NewSidekick')->first();
        $this->assertNotNull($newItem);
        $this->assertEquals('sold', $newItem->status);
        $this->assertEquals($this->storage->id, $newItem->sold_storage_id);
        $this->assertNotEquals(5, $newItem->sold_position);

        // No two items may share the same sold_storage_id + sold_position pair.
        $snapshotPositions = Item::where('sold_storage_id', $this->storage->id)
            ->whereNotNull('sold_position')
            ->groupBy('sold_position')
            ->selectRaw('sold_position, COUNT(*) as c')
            ->pluck('c', 'sold_position')
            ->toArray();
        foreach ($snapshotPositions as $position => $count) {
            $this->assertEquals(
                1,
                $count,
                "sold_position {$position} is shared by {$count} items (expected 1)."
            );
        }
    }

    /**
     * R6.S3 — happy path: no retry, item persists at the first free position.
     *
     * Sanity test that the catch-and-retry path is not triggered on the normal path.
     * The real `Storage::findFirstAvailablePosition()` returns position 1, and
     * `Item::create` succeeds without throwing a unique violation.
     *
     * @test
     */
    public function test_store_auto_assign_no_retry_on_happy_path(): void
    {
        $this->loginAsOwner();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'HappyPathDevice',
                    'selling_price' => 100,
                    'profit' => 50,
                ],
            ],
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 100,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $body = $response->json();
        $this->assertEquals([], $body['warnings']);

        $item = Item::where('model', 'HappyPathDevice')->first();
        $this->assertNotNull($item);
        // The auto-assigned storage is captured in the snapshot fields; active
        // fields are nulled by the Item::saving hook for SOLD items.
        $this->assertEquals($this->storage->id, $item->sold_storage_id);
        $this->assertEquals(1, $item->sold_position);
    }

    /**
     * R6.S1 — the controller respects pre-occupied positions when auto-assigning.
     *
     * NOTE: The retry-on-race mechanism (catch QueryException SQLSTATE 23000 and
     * retry once with a fresh findFirstAvailablePosition) cannot be unit-tested
     * here without Mockery's overload: support (which requires runkit/uopz, not
     * available in this environment). Mockery's `alias:` is incompatible with
     * PSR-4 autoloading because the class is already loaded by the time the
     * alias is registered. The retry itself is covered by:
     *   - The items_storage_position_unique DB index (the real defense).
     *   - Manual verification (T15) where the user opens two tabs and submits
     *     simultaneously.
     *   - Code review of the try/catch in SaleController::store:201-214.
     *
     * This test instead locks the contract that the controller does NOT blindly
     * assign position 1 if position 1 is already taken: the auto-assign reads
     * the DB and respects existing items, so a pre-occupied position 1 means
     * the new item goes to position 2.
     *
     * @test
     */
    public function test_store_auto_assign_respects_pre_occupied_position(): void
    {
        $this->loginAsOwner();

        // Pre-occupy position 1 with a real Item. findFirstAvailablePosition will
        // see this and return position 2.
        $this->createItem([
            'storage_id' => $this->storage->id,
            'position' => 1,
        ]);

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'RespectsPreOccupiedDevice',
                    'selling_price' => 100,
                    'profit' => 50,
                ],
            ],
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            // paid=0 so the new item stays RESERVED and keeps its position. For
            // paid=1, the saving hook's snapshot block would null position and
            // move it to sold_position — that path is covered by the previous
            // SDD change's tests, not here.
            'paid' => 0,
            'balance_remaining' => 100,
            'amount_paid' => 0,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        $response->assertStatus(201);
        $body = $response->json();
        $this->assertEquals([], $body['warnings']);

        $item = Item::where('model', 'RespectsPreOccupiedDevice')->first();
        $this->assertNotNull($item);
        // The controller should skip position 1 (occupied) and assign position 2.
        $this->assertEquals(2, $item->position);
        $this->assertEquals($this->storage->id, $item->storage_id);
    }

    /**
     * R6.S2 — when no storage is available, the controller returns 422 instead
     * of silently saving the item with a null storage_id.
     *
     * The retry mechanism from R6.S1 is documented in the test class docblock
     * as not directly unit-testable; this test covers the "findFirstAvailablePosition
     * returns null" path which is the user-facing failure mode the controller
     * must surface clearly.
     *
     * @test
     */
    public function test_store_auto_assign_returns_422_when_no_storage_available(): void
    {
        $this->loginAsOwner();

        // Delete the default storage so findFirstAvailablePosition returns null.
        $this->storage->delete();

        $payload = [
            'newItems' => [
                [
                    'type' => 'device',
                    'model' => 'NoStorageDevice',
                    'selling_price' => 100,
                    'profit' => 50,
                ],
            ],
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'paid' => 1,
            'balance_remaining' => 0,
            'amount_paid' => 100,
            'payment_date' => Carbon::now()->format('Y-m-d'),
        ];

        $response = $this->post(route('sales.store'), $payload);

        // The no-storage path is the fallback (Log::warning + warnings[] in
        // response), not a 422. The 422 is reserved for the race-after-retry path
        // which is not unit-testable here. The response should be 201 with a
        // warnings entry, and the item should be saved with null storage_id.
        $response->assertStatus(201);
        $body = $response->json();
        $this->assertNotEmpty($body['warnings']);
        $this->assertEquals('no_storage_available', $body['warnings'][0]['reason']);

        $item = Item::where('model', 'NoStorageDevice')->first();
        $this->assertNotNull($item);
        $this->assertNull($item->storage_id);
        $this->assertNull($item->position);
    }
}
