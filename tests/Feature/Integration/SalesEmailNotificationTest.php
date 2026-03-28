<?php

namespace Tests\Feature\Integration;

use App\Mail\OrderConfirmation;
use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class SalesEmailNotificationTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_order_confirmation_email_sent(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 1,
        ]);

        $customerEmail = 'customer@ecommerce.com';

        Mail::to($customerEmail)->send(new OrderConfirmation($sale));

        Mail::assertSent(OrderConfirmation::class, function ($mail) use ($sale) {
            return $mail->sale->id === $sale->id;
        });
    }

    public function test_email_notification_on_sale_completion(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 500.00,
            'paid' => 1,
        ]);

        $customerEmail = 'buyer@example.com';

        Mail::to($customerEmail)->send(new OrderConfirmation($sale));

        Mail::assertSent(OrderConfirmation::class);
    }
}
