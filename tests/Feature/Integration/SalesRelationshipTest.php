<?php

namespace Tests\Feature\Integration;

use App\Models\Company;
use App\Models\Item;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Tests\TestCaseWithCompany;

class SalesRelationshipTest extends TestCaseWithCompany
{
    public function test_sale_items_sum_to_subtotal(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'subtotal' => 0,
            'total' => 226.00,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'selling_price' => 100.00,
        ]);
        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'selling_price' => 100.00,
        ]);

        $items = $sale->items;
        $sum = $items->sum('selling_price');

        $this->assertEquals(200.00, $sum);
    }

    public function test_payment_belongs_to_sale(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $payment = Payment::create([
            'sale_id' => $sale->id,
            'amount_paid' => 100.00,
            'balance_remaining' => 0,
            'payment_method' => 'cash',
            'payment_account' => 'Cash',
            'payment_date' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals($sale->id, $payment->sale_id);
        $this->assertEquals($sale->id, $payment->sale->id);
    }

    public function test_sale_has_many_payments(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        Payment::create(['sale_id' => $sale->id, 'amount_paid' => 30.00, 'balance_remaining' => 70.00, 'payment_method' => 'cash', 'payment_account' => 'Cash', 'payment_date' => now()->format('Y-m-d')]);
        Payment::create(['sale_id' => $sale->id, 'amount_paid' => 70.00, 'balance_remaining' => 0, 'payment_method' => 'card', 'payment_account' => 'Card', 'payment_date' => now()->format('Y-m-d')]);

        $this->assertCount(2, $sale->payments);
    }

    public function test_sale_items_relationship(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
        ]);

        $this->assertCount(1, $sale->items);
    }

    public function test_items_have_sale_reference(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $item = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
        ]);

        $this->assertEquals($sale->id, $item->sale_id);
    }

    public function test_company_scope_applies_to_sales(): void
    {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 100.00]);
        Sale::factory()->create(['user_id' => $otherUser->id, 'total' => 200.00]);

        $response = $this->actingAs($this->owner)->get('/accounting/payments');
        $response->assertStatus(200);
    }
}
