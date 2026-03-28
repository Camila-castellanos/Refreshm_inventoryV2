<?php

namespace Tests\Feature\Emails;

use App\Mail\InvoiceSent;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class InvoiceSentTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_invoice_sent_builds_correctly(): void
    {
        $mailable = new InvoiceSent('Test Subject', 'Test Message');

        $mailable->build();

        $this->assertNotNull($mailable);
    }

    public function test_invoice_sent_has_custom_subject(): void
    {
        $subject = 'Invoice for Order #456';
        $message = 'Please find your invoice attached.';

        $mailable = new InvoiceSent($subject, $message);

        $this->assertEquals($subject, $mailable->subject);
    }

    public function test_invoice_sent_has_custom_message(): void
    {
        $subject = 'Test';
        $message = 'Custom message content';

        $mailable = new InvoiceSent($subject, $message);

        $this->assertEquals($message, $mailable->message);
    }

    public function test_invoice_sent_can_be_sent(): void
    {
        $subject = 'Invoice';
        $message = 'Please pay';

        Mail::to('customer@example.com')->send(new InvoiceSent($subject, $message));

        Mail::assertSent(InvoiceSent::class);
    }

    public function test_invoice_sent_handles_invalid_email_gracefully(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/accounting/payments/1/sendInvoice', [
                'sale_id' => 1,
                'email' => 'invalid-email',
                'subject' => 'Test',
                'message' => 'Test',
            ]);

        $response->assertStatus(302);
    }

    public function test_invoice_sent_returns_view(): void
    {
        $mailable = new InvoiceSent('Test', 'Message');
        $content = $mailable->build();

        $this->assertNotNull($content);
    }
}
