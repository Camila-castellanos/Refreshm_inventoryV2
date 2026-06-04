<?php

namespace Tests\Feature\Payments;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use Carbon\Carbon;
use Tests\TestCaseWithCompany;

/**
 * Covers the contract of PaymentController::getPaymentsSimpleList after the
 * fix-payments-list-and-storage-flow-hardening change (Item 1).
 *
 * Spec mapping: openspec/changes/fix-payments-list-and-storage-flow-hardening/specs/sales/spec.md
 *   R5.S1 — all-RESERVED sale appears
 *   R5.S2 — all-SOLD sale appears (regression)
 *   R5.S3 — mixed SOLD+RESERVED sale appears once
 *   R5.S4 — customer search returns an unpaid-only sale
 *   R5.S5 — ordering and limit cap
 */
class PaymentSimpleListVisibilityTest extends TestCaseWithCompany
{
    private const ENDPOINT = '/accounting/payments/simple';

    /** @test */
    public function test_simple_list_includes_reserved_only_sale(): void
    {
        // R5.S1: an all-RESERVED sale (no items with sold populated) must surface
        // in the simple list. This was broken by the pre-fix `whereNotNull('sold')`
        // filter.
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 500,
            'total' => 500,
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'status' => Item::STATUS_RESERVED,
            'selling_price' => 500,
        ]);

        $response = $this->actingAs($this->owner)->get(self::ENDPOINT);

        $response->assertStatus(200);
        $response->assertJsonFragment(['sale_id' => $sale->id]);
    }

    /** @test */
    public function test_simple_list_includes_all_sold_sale(): void
    {
        // R5.S2: regression — pre-fix behavior. All-SOLD sales must still appear.
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 1,
            'balance_remaining' => 0,
            'total' => 1000,
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => Carbon::now(),
            'status' => Item::STATUS_SOLD,
            'selling_price' => 1000,
        ]);

        $response = $this->actingAs($this->owner)->get(self::ENDPOINT);

        $response->assertStatus(200);
        $response->assertJsonFragment(['sale_id' => $sale->id]);
    }

    /** @test */
    public function test_simple_list_includes_mixed_status_sale(): void
    {
        // R5.S3: a sale with one SOLD + one RESERVED item appears exactly once.
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 750,
            'total' => 750,
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => Carbon::now(),
            'status' => Item::STATUS_SOLD,
            'selling_price' => 500,
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'status' => Item::STATUS_RESERVED,
            'selling_price' => 250,
        ]);

        $response = $this->actingAs($this->owner)->get(self::ENDPOINT);

        $response->assertStatus(200);

        $body = $response->json();
        $matches = collect($body)->where('sale_id', $sale->id);
        $this->assertCount(1, $matches, 'A mixed-status sale should appear exactly once.');
    }

    /** @test */
    public function test_simple_list_customer_search_finds_unpaid_sale(): void
    {
        // R5.S4: customer search must match a sale whose items are all RESERVED
        // and whose customer name is "Acme". Pre-fix, the whereNotNull('sold')
        // filter dropped this sale from the result set even when the search
        // branch matched on customer.
        //
        // NOTE: the controller's where clause does `where('items.customer', 'like', %q%)`
        // — it matches against the RAW customer field on the items row. If customer
        // is the numeric Customer.id, "Acme" won't match. To exercise the
        // "customer name search" path, the item's customer field must be set to the
        // name string. (The controller's response handler separately resolves the
        // numeric ID to a name for display.)
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'paid' => 0,
            'balance_remaining' => 300,
            'total' => 300,
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'status' => Item::STATUS_RESERVED,
            'customer' => 'Acme Corp', // string, not customer.id, so the LIKE matches
            'selling_price' => 300,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(self::ENDPOINT.'?q=Acme');

        $response->assertStatus(200);
        $response->assertJsonFragment(['sale_id' => $sale->id]);
    }

    /** @test */
    public function test_simple_list_orders_by_date_desc_capped_at_limit(): void
    {
        // R5.S5: ordering by date DESC, cap at the requested limit.
        $baseDate = Carbon::now();

        // 30 sales with monotonically increasing dates so the order is
        // deterministic. Use small total to keep the test fast.
        for ($i = 0; $i < 30; $i++) {
            $sale = Sale::factory()->create([
                'user_id' => $this->owner->id,
                'paid' => 1,
                'balance_remaining' => 0,
                'total' => 10,
                'date' => $baseDate->copy()->subMinutes($i),
            ]);

            Item::factory()->create([
                'sale_id' => $sale->id,
                'user_id' => $this->owner->id,
                'shop_id' => $this->shop->id,
                'storage_id' => $this->storage->id,
                'sold' => $baseDate->copy()->subMinutes($i),
                'status' => Item::STATUS_SOLD,
                'selling_price' => 10,
            ]);
        }

        $response = $this->actingAs($this->owner)
            ->get(self::ENDPOINT.'?limit=25');

        $response->assertStatus(200);

        $body = $response->json();
        $this->assertCount(25, $body, 'simpleList must cap results at the requested limit.');

        // Carbon serializes DATETIME to ISO 8601 in JSON responses. We assert the
        // newest date is "very close to now" rather than string-equality, because
        // microsecond rounding can differ between Carbon::now() at fixture time
        // and the response serialization.
        $responseDate = Carbon::parse($body[0]['date']);
        $diff = abs($responseDate->diffInSeconds($baseDate));
        $this->assertLessThan(
            5,
            $diff,
            'Newest sale should be at or near $baseDate (within 5 seconds). Got: '
            .$body[0]['date'].' (diff: '.$diff.'s)'
        );
    }
}
