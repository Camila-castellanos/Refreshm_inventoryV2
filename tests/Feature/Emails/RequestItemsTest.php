<?php

namespace Tests\Feature\Emails;

use App\Mail\RequestItems;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class RequestItemsTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_request_items_contains_items_data(): void
    {
        $items = [
            ['model' => 'iPhone 15', 'colour' => 'Black'],
            ['model' => 'Galaxy S24', 'colour' => 'Blue'],
        ];

        $mailable = new RequestItems('John Doe', 'john@example.com', 'Main Store', 'Urgent request', $items);

        $this->assertEquals('John Doe', $mailable->name);
        $this->assertEquals('john@example.com', $mailable->email);
        $this->assertEquals('Main Store', $mailable->store);
        $this->assertEquals('Urgent request', $mailable->notes);
        $this->assertCount(2, $mailable->items);
    }

    public function test_request_items_sent_with_valid_data(): void
    {
        $mailable = new RequestItems(
            'Test Name',
            'test@example.com',
            'Test Store',
            'Test Notes',
            [['model' => 'iPhone']]
        );

        Mail::to('recipient@example.com')->send($mailable);

        Mail::assertSent(RequestItems::class);
    }

    public function test_request_items_contains_store_info(): void
    {
        $mailable = new RequestItems(
            'Name',
            'email@test.com',
            'Downtown Store',
            'Notes',
            []
        );

        $this->assertEquals('Downtown Store', $mailable->store);
    }

    public function test_request_items_contains_notes(): void
    {
        $mailable = new RequestItems(
            'Name',
            'email@test.com',
            'Store',
            'Please deliver ASAP',
            []
        );

        $this->assertEquals('Please deliver ASAP', $mailable->notes);
    }
}
