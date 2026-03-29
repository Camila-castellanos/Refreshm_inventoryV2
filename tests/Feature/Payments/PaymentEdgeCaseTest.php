<?php

namespace Tests\Feature\Payments;

use App\Models\Item;
use App\Models\Payment;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class PaymentEdgeCaseTest extends TestCaseWithCompany
{
    public function test_payment_with_zero_amount_fails(): void
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
                'amount' => '0',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $response->assertStatus(500);
    }

    public function test_payment_greater_than_balance(): void
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
                'amount' => '150.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(0, $sale->balance_remaining);
        $this->assertEquals(1, $sale->paid);
    }

    public function test_multiple_partial_payments(): void
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
        $this->assertEquals(0, $sale->paid);

        $this->actingAs($this->owner)
            ->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
                'sale_id' => $sale->id,
                'amount' => '50.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'card',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $sale->refresh();
        $this->assertEquals(20.00, $sale->balance_remaining);
        $this->assertEquals(80.00, $sale->amount_paid);
        $this->assertEquals(0, $sale->paid);

        $this->actingAs($this->owner)
            ->post('/accounting/payments/'.$sale->id.'/invoice/paid', [
                'sale_id' => $sale->id,
                'amount' => '20.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'transfer',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $sale->refresh();
        $this->assertEquals(0, $sale->balance_remaining);
        $this->assertEquals(100.00, $sale->amount_paid);
        $this->assertEquals(1, $sale->paid);
    }

    public function test_payment_on_already_paid_sale(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 0,
            'amount_paid' => 100.00,
            'paid' => 1,
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
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(0, $sale->balance_remaining);
    }

    public function test_payment_validates_required_amount(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 100.00,
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
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $response->assertStatus(302);
    }

    public function test_payment_validates_required_sale_id(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/accounting/payments/1/invoice/paid', [
                'amount' => '50.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $response->assertStatus(302);
    }

    public function test_payment_with_negative_amount(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 100.00,
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
                'amount' => '-50.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $response->assertStatus(500);
    }

    public function test_payment_with_decimal_amount(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 99.99,
            'balance_remaining' => 99.99,
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
                'amount' => '33.33',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Bank Account',
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(66.66, $sale->balance_remaining);
    }

    public function test_remove_payment_updates_sale_status(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'balance_remaining' => 0,
            'amount_paid' => 100.00,
            'paid' => 1,
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
            'amount_paid' => 100.00,
            'balance_remaining' => 0,
        ]);

        $this->actingAs($this->owner)
            ->post('/payments/remove', [
                'id' => $payment->id,
                'sale_id' => $sale->id,
            ]);

        $sale->refresh();
        $this->assertEquals(0, $sale->paid);
    }

    public function test_edit_payment_adjusts_balance(): void
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
            ->post('/payments/edit', [
                'id' => $payment->id,
                'sale_id' => $sale->id,
                'paymentAmount' => '75.00',
                'paymentMethod' => 'card',
                'paymentAccount' => 'Bank Account',
                'paymentDate' => now()->format('Y-m-d'),
            ]);

        $sale->refresh();
        $this->assertEquals(25.00, $sale->balance_remaining);
        $this->assertEquals(75.00, $sale->amount_paid);
    }
}
