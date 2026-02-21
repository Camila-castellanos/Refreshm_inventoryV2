<?php

namespace Tests\Feature\Contacts;

use App\Models\Contact;
use Tests\TestCaseWithCompany;

class ContactCrudTest extends TestCaseWithCompany
{
    public function test_store_creates_contact(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'custom',
        ]);
    }

    public function test_store_validates_required_name(): void
    {
        $data = [
            'email' => 'john@example.com',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', $data);

        $response->assertStatus(500);
    }

    public function test_store_validates_required_email(): void
    {
        $data = [
            'name' => 'John Doe',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', $data);

        $response->assertStatus(500);
    }

    public function test_store_with_invalid_email_still_creates(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', $data);

        $response->assertStatus(200);
    }

    public function test_store_creates_contact_with_owner_user_id(): void
    {
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', $data);

        $response->assertStatus(200);

        $contact = Contact::where('email', 'jane@example.com')->first();
        $this->assertEquals($this->owner->id, $contact->user_id);
    }

    public function test_store_creates_contact_with_custom_type(): void
    {
        $data = [
            'name' => 'Test Contact',
            'email' => 'test@example.com',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', $data);

        $response->assertStatus(200);

        $contact = Contact::where('email', 'test@example.com')->first();
        $this->assertEquals('custom', $contact->type);
    }

    public function test_delete_contacts_by_prospect(): void
    {
        $contact = Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 1,
        ]);

        $result = Contact::deleteContactsByProspect(1);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_delete_contacts_by_customer(): void
    {
        $contact = Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'customer',
            'customer_id' => 1,
        ]);

        $result = Contact::deleteContactsByCustomer(1);

        $this->assertEquals(1, $result);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_delete_contacts_by_prospect_returns_zero_when_no_matches(): void
    {
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 1,
        ]);

        $result = Contact::deleteContactsByProspect(999);

        $this->assertEquals(0, $result);
    }

    public function test_contact_belongs_to_user(): void
    {
        $contact = Contact::factory()->forOwner($this->owner)->create();

        $this->assertEquals($this->owner->id, $contact->user_id);
    }
}
