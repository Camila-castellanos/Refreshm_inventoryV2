<?php

namespace Tests\Feature\Inventory;

use App\Mail\RequestItems;
use App\Models\IncomingRequest;
use App\Models\IncomingRequestItem;
use App\Models\Item;
use Illuminate\Support\Facades\Mail;
use Tests\TestCaseWithCompany;

class ItemRequestCrossUserTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /**
     * Test that a regular user can request items belonging to the owner
     * and the technical details are correctly preserved in the database and email.
     */
    public function test_regular_user_can_request_owner_items_with_full_details(): void
    {
        // 1. Create an item belonging to the OWNER with specific details
        $item = Item::factory()->create([
            'user_id' => $this->owner->id,
            'manufacturer' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'colour' => 'Natural Titanium',
            'grade' => 'A+',
            'imei' => '350000000000001',
            'selling_price' => 1200,
            'storage_id' => $this->storage->id,
            'date' => now(),
        ]);

        // 2. Verify that the regular user CANNOT find this item normally due to global scope
        $this->actingAs($this->user);
        $foundNormally = Item::find($item->id);
        $this->assertNull($foundNormally, 'User should not be able to find owner\'s item due to CompanyItemScope');

        // 3. Submit a request as the regular user for this item
        $requestData = [
            'name' => 'Zac A',
            'email' => 'zac@example.com',
            'store' => 'Mobile Tech Lab',
            'notes' => 'Will send label',
            'items' => [
                [
                    'id' => $item->id,
                    'selling_price' => 1200,
                    'currency' => 'CAD',
                ],
            ],
        ];

        $response = $this->post(route('items.request'), $requestData);

        // 4. Assertions
        $response->assertStatus(201);

        // Verify IncomingRequest was created
        $this->assertDatabaseHas('incoming_requests', [
            'name' => 'Zac A',
            'email' => 'zac@example.com',
        ]);

        $incomingRequest = IncomingRequest::latest()->first();

        // Verify IncomingRequestItem has the technical details from the DB item
        $this->assertDatabaseHas('incoming_request_items', [
            'incoming_request_id' => $incomingRequest->id,
            'original_item_id' => $item->id,
            'manufacturer' => 'Apple',
            'model' => 'iPhone 15 Pro',
            'colour' => 'Natural Titanium',
            'grade' => 'A+',
            'imei' => '350000000000001',
        ]);

        // Verify Email was sent with correct data (not N/A)
        Mail::assertSent(RequestItems::class, function ($mail) {
            $this->assertEquals('Zac A', $mail->name);
            $itemInMail = $mail->items[0];

            // Check that technical details are present in the mailable data
            return $itemInMail['manufacturer'] === 'Apple' &&
                   $itemInMail['model'] === 'iPhone 15 Pro' &&
                   $itemInMail['imei'] === '350000000000001' &&
                   $itemInMail['grade'] === 'A+';
        });
    }
}
