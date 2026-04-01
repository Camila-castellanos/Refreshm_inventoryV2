<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class PaymentModelTest extends TestCaseWithCompany
{
    public function test_payment_belongs_to_sale(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
        ]);

        $payment = Payment::factory()->create([
            'sale_id' => $sale->id,
        ]);

        $this->assertInstanceOf(Sale::class, $payment->sale);
    }
}
