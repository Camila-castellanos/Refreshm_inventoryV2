<?php

namespace Tests\Feature\Payments;

use App\Models\Item;
use App\Models\Payment;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class PaymentCrudTest extends TestCaseWithCompany
{
    public function test_can_view_payments_list(): void
    {
        Sale::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('items'));
    }

    public function test_can_view_payments_list_with_status_filter(): void
    {
        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 1,
        ]);

        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 50.00,
            'paid' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments?status=paid');

        $response->assertStatus(200);
    }

    public function test_can_view_payments_simple_list(): void
    {
        Sale::factory()->count(2)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments/simple');

        $response->assertStatus(200);
    }

    /**
     * The Payments page must use `sales.date` (the user-picked payment date in
     * the form) as the primary date source. The item's `partially_sold_at` (set
     * when the item becomes RESERVED) and `created_at` are fallbacks. This
     * aligns the Payments page with the simpleList endpoint, which already
     * uses `sales.date`.
     *
     * Regression scenario: a sale created today (created_at = today) with a
     * backdated `payment_date` (sales.date = 2 days ago) and a still-RESERVED
     * item. Pre-fix, the Payments page showed today (partially_sold_at) while
     * the simpleList showed 2 days ago (sales.date). Post-fix, both show
     * `sales.date`.
     *
     * @test
     */
    public function test_payments_page_uses_sale_date_not_partially_sold_at(): void
    {
        $today = now();
        $twoDaysAgo = $today->copy()->subDays(2);

        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 0, // unpaid → items stay RESERVED
            'date' => $twoDaysAgo, // user backdated the payment date
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        // Item is RESERVED (sold = null), with partially_sold_at = today (set
        // by the Item saving boot hook when transitioning to RESERVED).
        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'partially_sold_at' => $today,
            'status' => Item::STATUS_RESERVED,
            'selling_price' => 100,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments?status=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.date', $twoDaysAgo->format('Y-m-d'))
        );
    }

    /**
     * The Payments page must format `sales.date` in the user's timezone, not
     * in the app's default timezone (UTC). The simpleList endpoint already does
     * this via the Sale model's `serializeDate` method; the Payments page
     * previously formatted via Carbon::format('Y-m-d') which runs in app TZ.
     * For a user in UTC-3, a sale with `sales.date = 2026-06-02 02:30:00 UTC`
     * (= 2026-06-01 23:30 user time) would show as 2026-06-02 in the Payments
     * page but 2026-06-01 in the simpleList — a 1-day mismatch.
     *
     * @test
     */
    public function test_payments_page_formats_sale_date_in_user_timezone(): void
    {
        // Set the user's timezone to UTC-3 (Argentina). The SetUserTimezone
        // middleware reads this from the user record and sets app.user_timezone.
        $this->owner->update(['timezone' => 'America/Argentina/Buenos_Aires']);

        // Create a sale with date stored as 2026-06-02 02:30:00 UTC. In user
        // TZ (UTC-3) this is 2026-06-01 23:30:00. The Payments page must
        // display 2026-06-01 (the user-local calendar day), not 2026-06-02.
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 0,
            'date' => '2026-06-02 02:30:00', // stored as this UTC datetime
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => null,
            'partially_sold_at' => now(),
            'status' => Item::STATUS_RESERVED,
            'selling_price' => 100,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments?status=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.date', '2026-06-01') // user-local date (UTC-3 of 2026-06-02 02:30 UTC)
        );
    }

    public function test_can_record_payment(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 100.00,
            'amount_paid' => 0,
            'paid' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
                'sale_id' => $sale->id,
                'amount' => '50.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
                'paidNotes' => 'Test payment',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'amount_paid' => '50.00',
        ]);
    }

    public function test_record_payment_updates_sale_balance(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 100.00,
            'amount_paid' => 0,
            'paid' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $this->actingAs($this->owner)
            ->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
                'sale_id' => $sale->id,
                'amount' => '30.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $sale->refresh();

        $this->assertEquals(70.00, $sale->balance_remaining);
        $this->assertEquals(30.00, $sale->amount_paid);
    }

    public function test_record_payment_marks_sale_as_paid_when_full(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 100.00,
            'amount_paid' => 0,
            'paid' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $this->actingAs($this->owner)
            ->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
                'sale_id' => $sale->id,
                'amount' => '100.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $sale->refresh();

        $this->assertEquals(1, $sale->paid);
        $this->assertEquals(0, $sale->balance_remaining);
    }

    public function test_record_payment_partial(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 100.00,
            'amount_paid' => 0,
            'paid' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $this->actingAs($this->owner)
            ->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
                'sale_id' => $sale->id,
                'amount' => '25.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'card',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $sale->refresh();

        $this->assertEquals(0, $sale->paid);
        $this->assertEquals(75.00, $sale->balance_remaining);
    }

    public function test_can_remove_payment(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 50.00,
            'amount_paid' => 50.00,
            'paid' => 0,
        ]);

        $payment = Payment::factory()->create([
            'sale_id' => $sale->id,
            'amount_paid' => 50.00,
            'balance_remaining' => 50.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/payments/remove', [
                'id' => $payment->id,
                'sale_id' => $sale->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_remove_payment_restores_balance(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 50.00,
            'amount_paid' => 50.00,
            'paid' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $payment = Payment::factory()->create([
            'sale_id' => $sale->id,
            'amount_paid' => 50.00,
            'balance_remaining' => 50.00,
        ]);

        $this->actingAs($this->owner)
            ->post('/payments/remove', [
                'id' => $payment->id,
                'sale_id' => $sale->id,
            ]);

        $sale->refresh();

        $this->assertEquals(100.00, $sale->balance_remaining);
        $this->assertEquals(0, $sale->amount_paid);
    }

    public function test_can_edit_payment(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 50.00,
            'amount_paid' => 50.00,
            'paid' => 0,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
        ]);

        $payment = Payment::factory()->create([
            'sale_id' => $sale->id,
            'amount_paid' => 50.00,
            'balance_remaining' => 50.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/payments/edit', [
                'id' => $payment->id,
                'sale_id' => $sale->id,
                'paymentAmount' => '75.00',
                'paymentMethod' => 'card',
                'paymentAccount' => 'Bank Account',
                'paymentDate' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount_paid' => '75.00',
        ]);
    }

    public function test_can_add_items_to_existing_sale(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'subtotal' => 100.00,
            'tax' => 0,
            'flatTax' => 0,
            'discount' => 0,
            'amount_paid' => 0,
            'balance_remaining' => 100.00,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'selling_price' => 100.00,
            'cost' => 50.00,
            'sold' => now(),
        ]);

        $newItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'selling_price' => 50.00,
            'cost' => 25.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/payments/addNewItems', [
                'sale_id' => $sale->id,
                'items' => [
                    [
                        'id' => $newItem->id,
                        'selling_price' => 50.00,
                        'position' => 'Shelf A',
                        'storage_id' => $this->storage->id,
                    ],
                ],
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(150.00, $sale->total);
    }

    public function test_can_delete_sale_and_restore_items(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $item1 = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'type' => 'device',
        ]);

        $item2 = Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'type' => 'accessory',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/payments/delete', [
                'invoices' => [
                    ['sale_id' => $sale->id],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);

        $item1->refresh();
        $this->assertNull($item1->sale_id);
        $this->assertNull($item1->sold);
    }

    public function test_payments_require_authentication(): void
    {
        $response = $this->get('/accounting/payments');

        $response->assertRedirect('/login');
    }

    public function test_record_payment_requires_authentication(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
            'sale_id' => $sale->id,
            'amount' => '50.00',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_can_search_payments(): void
    {
        Sale::factory()->count(3)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments/search?q=test');

        $response->assertStatus(200);
    }
}
