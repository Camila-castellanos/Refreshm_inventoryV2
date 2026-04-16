<?php

namespace Tests\Unit\Services\Inventory;

use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Item;
use App\Models\Sale;
use App\Services\Inventory\AppendIncomingRequestToInvoiceService;
use App\Services\Inventory\Exceptions\AppendIncomingRequestConflictException;
use App\Services\Inventory\Exceptions\AppendIncomingRequestNoEligibleItemsException;
use Tests\TestCaseWithCompany;

class AppendIncomingRequestToInvoiceServiceTest extends TestCaseWithCompany
{
    private AppendIncomingRequestToInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->owner);
        $this->service = app(AppendIncomingRequestToInvoiceService::class);
    }

    public function test_full_append_marks_request_processed_and_keeps_existing_invoice_customer(): void
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
            'selling_price' => 90,
            'cost' => 45,
            'customer' => 'Request Customer',
        ]);

        $request = IncomingRequest::create([
            'name' => 'Request Customer',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $appendableItem->id,
            'selling_price' => 90,
            'cost' => 45,
            'customer' => 'Request Customer',
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->execute($request, $sale, 'svc-full-1', $this->owner->id);

        $request->refresh();
        $appendableItem->refresh();

        $this->assertSame('full', $result['state_transition']);
        $this->assertSame(1, $result['counts']['appended']);
        $this->assertSame(0, $result['counts']['pending']);
        $this->assertTrue((bool) $request->processed);
        $this->assertSame('Invoice Customer', $appendableItem->customer);
    }

    public function test_partial_append_keeps_request_unprocessed_with_remaining_items(): void
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

        $eligible = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'selling_price' => 70,
            'cost' => 35,
        ]);

        $sold = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 50,
            'cost' => 25,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $eligible->id,
            'selling_price' => 70,
            'cost' => 35,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $sold->id,
            'selling_price' => 50,
            'cost' => 25,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->execute($request, $sale, 'svc-partial-1', $this->owner->id);

        $request->refresh();

        $this->assertSame('partial', $result['state_transition']);
        $this->assertSame(1, $result['counts']['appended']);
        $this->assertSame(1, $result['counts']['pending']);
        $this->assertFalse((bool) $request->processed);
    }

    public function test_replay_returns_stored_payload_as_idempotent_replay(): void
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

        $appendable = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'selling_price' => 60,
            'cost' => 30,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $appendable->id,
            'selling_price' => 60,
            'cost' => 30,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $first = $this->service->execute($request, $sale, 'svc-replay-1', $this->owner->id);
        $second = $this->service->execute($request, $sale, 'svc-replay-1', $this->owner->id);

        $this->assertFalse($first['idempotent_replay']);
        $this->assertTrue($second['idempotent_replay']);
        $this->assertSame($first['counts']['appended'], $second['counts']['appended']);
    }

    public function test_throws_conflict_when_request_is_already_processed(): void
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
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => true,
        ]);

        $this->expectException(AppendIncomingRequestConflictException::class);
        $this->service->execute($request, $sale, 'svc-conflict-1', $this->owner->id);
    }

    public function test_throws_no_eligible_items_with_contract_message_when_none_can_be_appended(): void
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

        $soldItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 80,
            'cost' => 40,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $soldItem->id,
            'selling_price' => 80,
            'cost' => 40,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $this->expectException(AppendIncomingRequestNoEligibleItemsException::class);
        $this->expectExceptionMessage(AppendIncomingRequestToInvoiceService::ZERO_ELIGIBLE_MESSAGE);

        $this->service->execute($request, $sale, 'svc-none-1', $this->owner->id);
    }
}
