<?php

namespace Tests\Feature\Inventory;

use App\Models\Company;
use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Sale;
use App\Models\User;
use Tests\TestCaseWithCompany;

class IncomingRequestCompanyScopeTest extends TestCaseWithCompany
{
    public function test_list_excludes_incoming_requests_from_other_company(): void
    {
        $ownRequest = IncomingRequest::create([
            'name' => 'Own Request',
            'email' => 'own@example.com',
            'store' => 'Main',
            'user_id' => $this->owner->id,
            'processed' => false,
        ]);

        [, $otherRequest] = $this->createOtherCompanyIncomingRequest();

        $response = $this->actingAs($this->owner)
            ->getJson('/inventory/items/incoming-requests?processed=0');

        $response->assertOk();

        $requestIds = collect($response->json())->pluck('id');

        $this->assertTrue($requestIds->contains($ownRequest->id));
        $this->assertFalse($requestIds->contains($otherRequest->id));
    }

    public function test_cannot_delete_incoming_request_item_from_other_company(): void
    {
        [, , $otherRequestItem] = $this->createOtherCompanyIncomingRequestWithItem();

        $response = $this->actingAs($this->owner)
            ->deleteJson("/inventory/items/incoming-requests/items/{$otherRequestItem->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('incoming_request_items', [
            'id' => $otherRequestItem->id,
        ]);
    }

    public function test_cannot_delete_incoming_request_from_other_company(): void
    {
        [, $otherRequest] = $this->createOtherCompanyIncomingRequest();

        $response = $this->actingAs($this->owner)
            ->deleteJson("/inventory/items/incoming-requests/{$otherRequest->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('incoming_requests', [
            'id' => $otherRequest->id,
        ]);
    }

    public function test_cannot_append_incoming_request_from_other_company(): void
    {
        [, $otherRequest] = $this->createOtherCompanyIncomingRequest();

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

        $response = $this->actingAs($this->owner)
            ->postJson("/inventory/items/incoming-requests/{$otherRequest->id}/append-to-invoice", [
                'sale_id' => $sale->id,
                'idempotency_key' => 'cross-company-append-1',
            ]);

        $response->assertStatus(404);
    }

    private function createOtherCompanyIncomingRequest(): array
    {
        $otherCompany = Company::factory()->create();
        $otherOwner = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        $otherRequest = IncomingRequest::withoutGlobalScopes()->create([
            'name' => 'Other Request',
            'email' => 'other@example.com',
            'store' => 'Other',
            'user_id' => $otherOwner->id,
            'processed' => false,
        ]);

        return [$otherOwner, $otherRequest];
    }

    private function createOtherCompanyIncomingRequestWithItem(): array
    {
        [$otherOwner, $otherRequest] = $this->createOtherCompanyIncomingRequest();

        $otherRequestItem = IncomingRequestItem::withoutGlobalScopes()->create([
            'incoming_request_id' => $otherRequest->id,
            'date' => now(),
            'user_id' => $otherOwner->id,
            'type' => 'device',
            'currency' => 'CAD',
        ]);

        return [$otherOwner, $otherRequest, $otherRequestItem];
    }
}
