<?php

namespace Tests\Feature\Inventory;

use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tests\TestCaseWithCompany;

class IncomingRequestAppendToInvoiceTest extends TestCaseWithCompany
{
    private const ZERO_ELIGIBLE_MESSAGE = 'No items from this request can be added to the selected invoice because they are no longer eligible (already sold, unavailable, or not visible).';

    public function test_append_requires_authentication(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $response = $this->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
            'sale_id' => 1,
            'idempotency_key' => 'abc-123',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_append_requires_inventory_active_permission_like_create_invoice_flow(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

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

        $unauthorizedUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => ['Inventory' => ['Sold']],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'abc-123',
            ]);

        $response->assertStatus(403);
    }

    public function test_append_validates_required_fields_without_customer_mismatch_flag(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'confirm_customer_mismatch' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sale_id', 'idempotency_key'])
            ->assertJsonMissingValidationErrors(['confirm_customer_mismatch']);
    }

    public function test_append_all_eligible_items_marks_request_processed_and_keeps_sale_customer(): void
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
            'customer' => 'Different Request Customer',
        ]);

        $request = IncomingRequest::create([
            'name' => 'Different Request Customer',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
            'shipping' => ['label' => 'Express', 'value' => 25],
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $appendableItem->id,
            'selling_price' => 80,
            'cost' => 40,
            'customer' => 'Different Request Customer',
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'append-full-1',
            ]);

        $response->assertOk()
            ->assertJsonPath('counts.appended', 1)
            ->assertJsonPath('counts.skipped', 0)
            ->assertJsonPath('state_transition', 'full')
            ->assertJsonPath('idempotent_replay', false);

        $appendableItem->refresh();
        $request->refresh();

        $this->assertSame($sale->id, $appendableItem->sale_id);
        $this->assertSame('Invoice Customer', $appendableItem->customer);
        $this->assertTrue((bool) $request->processed);
        $this->assertDatabaseMissing('incoming_request_items', ['id' => $requestItem->id]);
    }

    public function test_append_partial_eligible_items_keeps_request_open_with_remainder(): void
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

        $eligibleItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'selling_price' => 60,
            'cost' => 30,
        ]);

        $alreadySoldItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 75,
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
            'original_item_id' => $eligibleItem->id,
            'selling_price' => 60,
            'cost' => 30,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $skippedRequestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $alreadySoldItem->id,
            'selling_price' => 75,
            'cost' => 40,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'append-partial-1',
            ]);

        $response->assertOk()
            ->assertJsonPath('counts.appended', 1)
            ->assertJsonPath('counts.skipped', 1)
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonPath('state_transition', 'partial')
            ->assertJsonPath('skipped_items.0.incoming_request_item_id', $skippedRequestItem->id)
            ->assertJsonPath('skipped_items.0.reason', 'already_sold');

        $request->refresh();
        $this->assertFalse((bool) $request->processed);
        $this->assertDatabaseHas('incoming_request_items', ['id' => $skippedRequestItem->id]);
    }

    public function test_append_returns_exact_422_message_when_zero_items_eligible(): void
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

        $alreadySoldItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 75,
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
            'original_item_id' => $alreadySoldItem->id,
            'selling_price' => 75,
            'cost' => 40,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'append-none-1',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', self::ZERO_ELIGIBLE_MESSAGE);

        $request->refresh();
        $this->assertFalse((bool) $request->processed);
    }

    public function test_append_returns_validation_error_for_non_existent_sale(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => 999999,
                'idempotency_key' => 'append-missing-sale-1',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sale_id']);
    }

    public function test_append_accepts_paid_sale_for_append_flow(): void
    {
        $closedSale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'subtotal' => 100,
            'total' => 100,
            'amount_paid' => 100,
            'balance_remaining' => 0,
            'tax' => 0,
            'flatTax' => 0,
            'discount' => 0,
            'paid' => 2,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'test@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $appendableItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'selling_price' => 65,
            'cost' => 25,
        ]);

        IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $appendableItem->id,
            'selling_price' => 65,
            'cost' => 25,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $closedSale->id,
                'idempotency_key' => 'append-paid-sale-1',
            ]);

        $response->assertOk()
            ->assertJsonPath('counts.appended', 1)
            ->assertJsonPath('sale_id', $closedSale->id);
    }

    public function test_append_emits_single_structured_info_event_for_success_attempt(): void
    {
        Log::spy();

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

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Request-Id', 'corr-success-1')
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'append-telemetry-success-1',
            ]);

        $response->assertOk();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $message, array $context) use ($request, $sale) {
                return $message === 'incoming_request.append_to_invoice.attempt'
                    && $context['correlation_id'] === 'corr-success-1'
                    && $context['request_id'] === $request->id
                    && $context['sale_id'] === $sale->id
                    && $context['appended_count'] === 1
                    && $context['skipped_count'] === 0
                    && $context['state_transition'] === 'full'
                    && $context['outcome'] === 'success';
            });
    }

    public function test_append_emits_single_structured_warning_event_for_business_failure_attempt(): void
    {
        Log::spy();

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

        $alreadySoldItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 75,
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
            'original_item_id' => $alreadySoldItem->id,
            'selling_price' => 75,
            'cost' => 40,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Request-Id', 'corr-failure-1')
            ->post("/inventory/items/incoming-requests/{$request->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'append-telemetry-fail-1',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', self::ZERO_ELIGIBLE_MESSAGE);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($request, $sale) {
                return $message === 'incoming_request.append_to_invoice.failure'
                    && $context['correlation_id'] === 'corr-failure-1'
                    && $context['request_id'] === $request->id
                    && $context['sale_id'] === $sale->id
                    && $context['status_code'] === 422
                    && $context['error_class'] === 'AppendIncomingRequestNoEligibleItemsException';
            });
    }
}
