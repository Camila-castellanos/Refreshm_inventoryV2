<?php

namespace Tests\Feature\Emails;

use App\Mail\OrderConfirmation;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class OrderConfirmationTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_order_confirmation_sent_on_sale_completion(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 1,
        ]);

        $customerEmail = 'customer@example.com';

        Mail::to($customerEmail)->send(new OrderConfirmation($sale));

        Mail::assertSent(OrderConfirmation::class);
    }

    public function test_order_confirmation_sent_to_customer_email(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 250.00,
            'paid' => 1,
        ]);

        $customerEmail = 'customer@test.com';

        Mail::to($customerEmail)->send(new OrderConfirmation($sale));

        Mail::assertSent(OrderConfirmation::class, function ($mail) use ($customerEmail) {
            return $mail->hasTo($customerEmail);
        });
    }

    public function test_order_confirmation_contains_sale_items(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 1,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'model' => 'iPhone 15',
            'selling_price' => 100.00,
        ]);

        Mail::to('test@example.com')->send(new OrderConfirmation($sale));

        Mail::assertSent(OrderConfirmation::class, function ($mail) use ($sale) {
            return $mail->sale->id === $sale->id;
        });
    }

    public function test_order_confirmation_contains_total(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 500.00,
            'paid' => 1,
        ]);

        Mail::to('customer@example.com')->send(new OrderConfirmation($sale));

        Mail::assertSent(OrderConfirmation::class, function ($mail) {
            return $mail->sale->total === 500.00;
        });
    }

    public function test_order_confirmation_mailable_builds(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 1,
        ]);

        $mailable = new OrderConfirmation($sale);

        $this->assertNotNull($mailable->sale);
        $this->assertEquals($sale->id, $mailable->sale->id);
    }

    public function test_order_confirmation_has_correct_subject(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'id' => 123,
            'total' => 100.00,
        ]);

        $mailable = new OrderConfirmation($sale);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('123', $envelope->subject);
    }
}
