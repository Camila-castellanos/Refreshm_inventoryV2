<?php

namespace Tests\Feature\Contacts;

use App\Models\Contact;
use Tests\TestCaseWithCompany;

class ContactEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_contact_without_data_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', []);

        $response->assertStatus(500);
    }

    public function test_create_contact_with_name_too_long_allows(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => $longName,
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(200);
    }

    public function test_create_contact_with_duplicate_email_allows(): void
    {
        Contact::factory()->forOwner($this->owner->id)->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Another Contact',
                'email' => 'duplicate@example.com',
            ]);

        $response->assertStatus(200);
    }

    public function test_create_contact_with_email_too_long_allows(): void
    {
        $longEmail = str_repeat('a', 250).'@example.com';

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Test Contact',
                'email' => $longEmail,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_contact_without_authentication_fails(): void
    {
        $response = $this->post('/store/contact', [
            'name' => 'Test Contact',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_delete_contacts_by_prospect_with_nonexistent_id_returns_zero(): void
    {
        $result = Contact::deleteContactsByProspect(99999);

        $this->assertEquals(0, $result);
    }

    public function test_delete_contacts_by_customer_with_nonexistent_id_returns_zero(): void
    {
        $result = Contact::deleteContactsByCustomer(99999);

        $this->assertEquals(0, $result);
    }

    public function test_delete_contacts_by_prospect_removes_multiple(): void
    {
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 100,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 100,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 100,
        ]);

        $result = Contact::deleteContactsByProspect(100);

        $this->assertEquals(3, $result);
        $this->assertDatabaseMissing('contacts', ['prospect_id' => 100, 'type' => 'prospect']);
    }

    public function test_delete_contacts_by_customer_removes_multiple(): void
    {
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'customer',
            'customer_id' => 200,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'customer',
            'customer_id' => 200,
        ]);

        $result = Contact::deleteContactsByCustomer(200);

        $this->assertEquals(2, $result);
        $this->assertDatabaseMissing('contacts', ['customer_id' => 200, 'type' => 'customer']);
    }

    public function test_create_contact_always_sets_type_to_custom(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Test Contact',
                'email' => 'test@example.com',
                'type' => 'prospect',
            ]);

        $response->assertStatus(200);

        $contact = Contact::where('email', 'test@example.com')->first();
        $this->assertEquals('custom', $contact->type);
    }

    public function test_create_contact_with_null_name_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => null,
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_contact_with_null_email_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Test Contact',
                'email' => null,
            ]);

        $response->assertStatus(500);
    }

    public function test_create_contact_with_empty_name_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => '',
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_contact_with_empty_email_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Test Contact',
                'email' => '',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_contact_with_max_length_name(): void
    {
        $maxName = str_repeat('A', 255);

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => $maxName,
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(200);
    }

    public function test_create_contact_with_max_length_email(): void
    {
        $maxEmail = str_repeat('a', 243).'@example.com';

        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Test Contact',
                'email' => $maxEmail,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_contact_without_type_field_sets_default(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/store/contact', [
                'name' => 'Test Contact',
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(200);

        $contact = Contact::where('email', 'test@example.com')->first();
        $this->assertEquals('custom', $contact->type);
    }

    public function test_delete_contacts_only_affects_specific_prospect(): void
    {
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 300,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 300,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'prospect',
            'prospect_id' => 301,
        ]);

        $result = Contact::deleteContactsByProspect(300);

        $this->assertEquals(2, $result);
        $this->assertDatabaseHas('contacts', ['prospect_id' => 301, 'type' => 'prospect']);
    }

    public function test_delete_contacts_only_affects_specific_customer(): void
    {
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'customer',
            'customer_id' => 400,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'customer',
            'customer_id' => 400,
        ]);
        Contact::factory()->forOwner($this->owner->id)->create([
            'type' => 'customer',
            'customer_id' => 401,
        ]);

        $result = Contact::deleteContactsByCustomer(400);

        $this->assertEquals(2, $result);
        $this->assertDatabaseHas('contacts', ['customer_id' => 401, 'type' => 'customer']);
    }
}
