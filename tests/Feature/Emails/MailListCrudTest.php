<?php

namespace Tests\Feature\Emails;

use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\MailList;
use Tests\TestCaseWithCompany;

class MailListCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_mail_list_page(): void
    {
        MailList::factory()->forOwner($this->owner)->create();
        Contact::factory()->forOwner($this->owner)->create();
        EmailTemplate::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/mailing_list');

        $response->assertStatus(200);
    }

    public function test_store_creates_mail_list(): void
    {
        $data = [
            'title' => 'Newsletter Subscribers',
            'names' => json_encode(['John Doe', 'Jane Doe']),
            'emails' => json_encode(['john@example.com', 'jane@example.com']),
        ];

        $response = $this->actingAs($this->owner)
            ->post('/mailing_list', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('mail_lists', [
            'title' => 'Newsletter Subscribers',
        ]);
    }

    public function test_store_validates_required_title(): void
    {
        $data = [
            'names' => json_encode(['John Doe']),
            'emails' => json_encode(['john@example.com']),
        ];

        $response = $this->actingAs($this->owner)
            ->post('/mailing_list', $data);

        $response->assertStatus(500);
    }

    public function test_store_validates_required_names(): void
    {
        $data = [
            'title' => 'Test List',
            'emails' => json_encode(['john@example.com']),
        ];

        $response = $this->actingAs($this->owner)
            ->post('/mailing_list', $data);

        $response->assertStatus(500);
    }

    public function test_store_validates_required_emails(): void
    {
        $data = [
            'title' => 'Test List',
            'names' => json_encode(['John Doe']),
        ];

        $response = $this->actingAs($this->owner)
            ->post('/mailing_list', $data);

        $response->assertStatus(500);
    }

    public function test_update_modifies_mail_list(): void
    {
        $mailList = MailList::factory()->forOwner($this->owner)->create([
            'title' => 'Original Title',
        ]);

        $data = [
            'title' => 'Updated Title',
            'names' => json_encode(['New Name']),
            'emails' => json_encode(['new@example.com']),
        ];

        $response = $this->actingAs($this->owner)
            ->put("/mailing_list/{$mailList->id}", $data);

        $response->assertStatus(200);

        $mailList->refresh();
        $this->assertEquals('Updated Title', $mailList->title);
    }

    public function test_destroy_deletes_mail_list(): void
    {
        $mailList = MailList::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->delete("/mailing_list/{$mailList->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('mail_lists', ['id' => $mailList->id]);
    }

    public function test_mail_list_belongs_to_user(): void
    {
        $mailList = MailList::factory()->forOwner($this->owner)->create();

        $this->assertEquals($this->owner->id, $mailList->user_id);
    }

    public function test_mail_list_stores_json_names_and_emails(): void
    {
        $data = [
            'title' => 'JSON Test List',
            'names' => json_encode(['Alice', 'Bob', 'Charlie']),
            'emails' => json_encode(['alice@test.com', 'bob@test.com', 'charlie@test.com']),
        ];

        $response = $this->actingAs($this->owner)
            ->post('/mailing_list', $data);

        $response->assertStatus(200);

        $mailList = MailList::withoutGlobalScopes()->where('title', 'JSON Test List')->first();

        $this->assertNotNull($mailList);

        $names = json_decode($mailList->names);
        $emails = json_decode($mailList->emails);

        $this->assertCount(3, $names);
        $this->assertCount(3, $emails);
    }
}
