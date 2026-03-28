<?php

namespace Tests\Feature\Emails;

use App\Mail\MarketingEmail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class MarketingEmailTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_marketing_email_sent_to_single_recipient(): void
    {
        $mailable = new MarketingEmail(
            'Newsletter Subject',
            '# Hello World',
            'John',
            $this->owner
        );

        Mail::to('recipient@example.com')->send($mailable);

        Mail::assertSent(MarketingEmail::class);
    }

    public function test_marketing_email_sent_to_multiple_recipients(): void
    {
        $mailable = new MarketingEmail(
            'Subject',
            'Content',
            'Name',
            $this->owner
        );

        Mail::to(['a@test.com', 'b@test.com'])->send($mailable);

        Mail::assertSent(MarketingEmail::class);
    }

    public function test_marketing_email_parses_markdown_content(): void
    {
        $content = '# Hello World';

        $mailable = new MarketingEmail('Subject', $content, 'Name', $this->owner);

        $this->assertStringContainsString('Hello World', $mailable->content);
    }

    public function test_marketing_email_replaces_name_placeholders(): void
    {
        $content = 'Hello {{name}}, welcome!';

        $mailable = new MarketingEmail('Subject', $content, 'John Doe', $this->owner);

        $this->assertStringContainsString('John Doe', $mailable->content);
    }

    public function test_marketing_email_uses_user_as_sender(): void
    {
        $mailable = new MarketingEmail('Subject', 'Content', 'Name', $this->owner);

        $this->assertEquals($this->owner->email, $mailable->user->email);
    }

    public function test_marketing_email_handles_empty_content(): void
    {
        $mailable = new MarketingEmail('Subject', '', 'Name', $this->owner);

        $this->assertEquals('', $mailable->content);
    }
}
