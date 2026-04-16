<?php

namespace Tests\Feature\Inventory;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Tests\TestCaseWithCompany;

class IncomingRequestInvoicePreviewTest extends TestCaseWithCompany
{
    public function test_preview_returns_compact_schema_with_max_four_items_and_overflow_hint_support(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->owner->id,
            'customer' => 'Preview Customer',
        ]);

        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 2,
            'date' => '2026-04-15 10:00:00',
        ]);

        Item::factory()->count(5)->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => null,
            'sale_id' => $sale->id,
            'sold' => now(),
            'customer' => (string) $customer->id,
            'type' => 'device',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/inventory/items/incoming-requests/invoices/{$sale->id}/preview");

        $response->assertOk()
            ->assertJsonPath('sale_id', $sale->id)
            ->assertJsonPath('customer', 'Preview Customer')
            ->assertJsonPath('paid_status', 'Paid')
            ->assertJsonPath('total_items', 5)
            ->assertJsonCount(4, 'items_preview')
            ->assertJsonMissingPath('subtotal')
            ->assertJsonMissingPath('amount_paid')
            ->assertJsonStructure([
                'sale_id',
                'customer',
                'date',
                'paid_status',
                'total_items',
                'items_preview' => [
                    '*' => ['item_id', 'label', 'type', 'identifier', 'selling_price', 'currency'],
                ],
            ]);
    }

    public function test_preview_returns_deleted_item_fallback_values_when_item_fields_are_unresolvable(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 1,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => null,
            'sale_id' => $sale->id,
            'sold' => now(),
            'model' => null,
            'manufacturer' => null,
            'imei' => null,
            'customer' => null,
            'type' => '',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/inventory/items/incoming-requests/invoices/{$sale->id}/preview");

        $response->assertOk()
            ->assertJsonPath('total_items', 1)
            ->assertJsonPath('items_preview.0.label', 'Deleted item')
            ->assertJsonPath('items_preview.0.identifier', 'N/A')
            ->assertJsonPath('items_preview.0.type', 'unknown');
    }

    public function test_preview_requires_authentication(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->getJson("/inventory/items/incoming-requests/invoices/{$sale->id}/preview");

        $response->assertStatus(401);
    }

    public function test_preview_returns_empty_snapshot_for_invoice_without_sold_items(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/inventory/items/incoming-requests/invoices/{$sale->id}/preview");

        $response->assertOk()
            ->assertJsonPath('sale_id', $sale->id)
            ->assertJsonPath('total_items', 0)
            ->assertJsonPath('items_preview', []);
    }

    public function test_preview_requires_inventory_active_permission_like_append_flow(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $unauthorizedUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'page_permissions' => ['Inventory' => ['Sold']],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->getJson("/inventory/items/incoming-requests/invoices/{$sale->id}/preview");

        $response->assertStatus(403);
    }

    public function test_preview_returns_not_found_for_missing_sale(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/inventory/items/incoming-requests/invoices/999999/preview');

        $response->assertStatus(404);
    }
}
