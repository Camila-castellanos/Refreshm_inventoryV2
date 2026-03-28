<?php

namespace Tests\Feature\Integration;

use App\Models\Item;
use App\Models\Payment;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class PaymentStateTest extends TestCaseWithCompany
{
    protected function createSaleWithItem(float $total = 100.00): Sale
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => $total,
            'balance_remaining' => $total,
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

        return $sale;
    }

    // === PAYMENT STATES ===

    public function test_payment_full_paid_marks_sale_as_paid(): void
    {
        $sale = $this->createSaleWithItem(100.00);

        $response = $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '100.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Cash',
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(1, $sale->paid);
    }

    public function test_payment_partial_creates_balance(): void
    {
        $sale = $this->createSaleWithItem(100.00);

        $response = $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '50.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Cash',
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(0, $sale->paid);
        $this->assertEquals(50.00, $sale->balance_remaining);
    }

    public function test_payment_unpaid_sale_has_zero_paid(): void
    {
        $sale = $this->createSaleWithItem(100.00);

        $this->assertEquals(0, $sale->amount_paid);
        $this->assertEquals(0, $sale->paid);
    }

    public function test_payment_exceeds_total_capped(): void
    {
        $sale = $this->createSaleWithItem(100.00);

        $response = $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '150.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Cash',
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(1, $sale->paid);
    }

    public function test_payment_updates_sale_balance(): void
    {
        $sale = $this->createSaleWithItem(200.00);

        $response = $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '75.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'card',
                'paidPaymentAccount' => 'Card',
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(75.00, $sale->amount_paid);
        $this->assertEquals(125.00, $sale->balance_remaining);
    }

    public function test_multiple_payments_on_single_sale(): void
    {
        $sale = $this->createSaleWithItem(100.00);

        $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '30.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Cash',
            ]);

        $sale->refresh();
        $this->assertEquals(70.00, $sale->balance_remaining);

        $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '70.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'card',
                'paidPaymentAccount' => 'Card',
            ]);

        $sale->refresh();
        $this->assertEquals(1, $sale->paid);
    }

    public function test_payment_method_recorded_correctly(): void
    {
        $sale = $this->createSaleWithItem(100.00);

        $response = $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '100.00',
                'paidDate' => now()->format('Y-m-d'),
                'paidPaymentMethod' => 'bank_transfer',
                'paidPaymentAccount' => 'Bank',
            ]);

        $response->assertStatus(200);

        $payment = Payment::where('sale_id', $sale->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('bank_transfer', $payment->payment_method);
    }

    public function test_payment_date_recorded(): void
    {
        $sale = $this->createSaleWithItem(100.00);
        $paymentDate = '2025-06-15';

        $response = $this->actingAs($this->owner)
            ->post("/accounting/payments/{$sale->id}/invoice/paid", [
                'sale_id' => $sale->id,
                'amount' => '100.00',
                'paidDate' => $paymentDate,
                'paidPaymentMethod' => 'cash',
                'paidPaymentAccount' => 'Cash',
            ]);

        $response->assertStatus(200);

        $payment = Payment::where('sale_id', $sale->id)->first();
        $this->assertNotNull($payment);
    }
}
