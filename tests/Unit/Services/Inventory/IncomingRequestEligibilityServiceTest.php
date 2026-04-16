<?php

namespace Tests\Unit\Services\Inventory;

use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Item;
use App\Models\Storage;
use App\Models\User;
use App\Services\Inventory\IncomingRequestEligibilityService;
use Tests\TestCaseWithCompany;

class IncomingRequestEligibilityServiceTest extends TestCaseWithCompany
{
    private IncomingRequestEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(IncomingRequestEligibilityService::class);
        $this->actingAs($this->owner);
    }

    public function test_returns_missing_original_item_when_original_item_id_is_null(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => null,
            'selling_price' => 10,
            'cost' => 5,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->evaluate($requestItem);

        $this->assertFalse($result['eligible']);
        $this->assertSame('missing_original_item', $result['reason']);
    }

    public function test_returns_already_sold_when_original_item_is_sold(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $soldItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => now(),
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $soldItem->id,
            'selling_price' => 10,
            'cost' => 5,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->evaluate($requestItem);

        $this->assertFalse($result['eligible']);
        $this->assertSame('already_sold', $result['reason']);
    }

    public function test_returns_on_hold_when_original_item_is_on_hold(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $heldItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'hold' => now(),
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $heldItem->id,
            'selling_price' => 10,
            'cost' => 5,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->evaluate($requestItem);

        $this->assertFalse($result['eligible']);
        $this->assertSame('on_hold', $result['reason']);
    }

    public function test_returns_not_visible_when_item_is_outside_current_scope(): void
    {
        $otherCompanyUser = User::factory()->create([
            'company_id' => null,
            'role' => 'USER',
        ]);
        $otherCompanyStorage = Storage::factory()->create([
            'company_id' => null,
        ]);

        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $foreignItem = Item::withoutGlobalScopes()->create([
            'date' => now(),
            'manufacturer' => 'Apple',
            'model' => 'iPhone',
            'colour' => 'Black',
            'battery' => '90%',
            'grade' => 'A',
            'cost' => 100,
            'imei' => '900000000000001',
            'selling_price' => 150,
            'user_id' => $otherCompanyUser->id,
            'shop_id' => null,
            'storage_id' => $otherCompanyStorage->id,
            'vendor_id' => null,
            'type' => 'device',
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $foreignItem->id,
            'selling_price' => 10,
            'cost' => 5,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->evaluate($requestItem);

        $this->assertFalse($result['eligible']);
        $this->assertSame('not_visible', $result['reason']);
    }

    public function test_returns_unavailable_when_item_has_no_active_storage(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $unavailableItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => null,
            'sold' => null,
            'hold' => null,
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $unavailableItem->id,
            'selling_price' => 10,
            'cost' => 5,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->evaluate($requestItem);

        $this->assertFalse($result['eligible']);
        $this->assertSame('unavailable', $result['reason']);
    }

    public function test_returns_eligible_for_available_visible_unsold_item(): void
    {
        $request = IncomingRequest::create([
            'name' => 'Requester',
            'email' => 'req@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        $eligibleItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'hold' => null,
        ]);

        $requestItem = IncomingRequestItem::create([
            'incoming_request_id' => $request->id,
            'original_item_id' => $eligibleItem->id,
            'selling_price' => 10,
            'cost' => 5,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        $result = $this->service->evaluate($requestItem);

        $this->assertTrue($result['eligible']);
        $this->assertNull($result['reason']);
        $this->assertSame($eligibleItem->id, $result['item']?->id);
    }
}
