<?php

namespace Tests\Feature\Emails;

use App\Mail\InvoiceSent;
use App\Mail\MarketingEmail;
use App\Mail\OrderConfirmation;
use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class EmailEdgeCaseTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_email_fake_intercepts_all_emails(): void
    {
        Mail::to('test@example.com')->send(new OrderConfirmation(new Sale));

        Mail::assertSent(OrderConfirmation::class);
    }

    public function test_email_sent_assertion_passes(): void
    {
        Mail::to('customer@test.com')->send(new OrderConfirmation(new Sale));

        Mail::assertSent(OrderConfirmation::class, function ($mail) {
            return $mail->hasTo('customer@test.com');
        });
    }

    public function test_email_not_sent_assertion_passes(): void
    {
        Mail::to('customer@test.com')->send(new OrderConfirmation(new Sale));

        Mail::assertNotSent(InvoiceSent::class);
    }

    public function test_email_with_null_message(): void
    {
        $mailable = new InvoiceSent('Subject', null);

        $this->assertNull($mailable->message);
    }

    public function test_email_with_special_characters(): void
    {
        $mailable = new MarketingEmail(
            'Special Chars: ñáéíóú & < > " \'',
            'Content with special: ñáéíóú',
            'Name with é',
            $this->owner
        );

        $this->assertNotNull($mailable);
    }

    public function test_multiple_emails_sent_in_sequence(): void
    {
        $sale1 = Sale::factory()->create(['user_id' => $this->owner->id]);
        $sale2 = Sale::factory()->create(['user_id' => $this->owner->id]);

        Mail::to('a@test.com')->send(new OrderConfirmation($sale1));
        Mail::to('b@test.com')->send(new OrderConfirmation($sale2));

        Mail::assertSent(OrderConfirmation::class, 2);
    }
}
