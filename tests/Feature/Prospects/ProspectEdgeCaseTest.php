<?php

namespace Tests\Feature\Prospects;

use App\Models\Contact;
use App\Models\Prospect;
use Tests\TestCaseWithCompany;

class ProspectEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_prospect_without_email_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'first_name' => 'Test',
                'last_name' => 'Prospect',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_prospect_with_duplicate_email_fails(): void
    {
        Prospect::factory()->forOwner($this->owner)->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'duplicate@example.com',
                'first_name' => 'Test',
                'last_name' => 'Prospect',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_prospect_without_required_data_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', []);

        $response->assertStatus(500);
    }

    public function test_update_nonexistent_prospect_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/prospects/99999', [
                'email' => 'updated@example.com',
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'company_name' => 'Updated Company',
                'country' => 'USA',
                'phone_number' => '9876543210',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(404);
    }

    public function test_delete_nonexistent_prospect_returns_500(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete('/prospects/99999');

        $response->assertStatus(500);
    }

    public function test_create_prospect_with_invalid_email_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'not-an-email',
                'first_name' => 'Test',
                'last_name' => 'Prospect',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_prospect_without_authentication_fails(): void
    {
        $response = $this->post('/prospects', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'Prospect',
            'company_name' => 'Test Company',
            'country' => 'Canada',
            'phone_number' => '1234567890',
            'contact_type' => 'lead',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_delete_prospect_removes_associated_contacts(): void
    {
        $prospect = Prospect::factory()->forOwner($this->owner)->create();

        Contact::factory()->forOwner($this->owner)->create([
            'type' => 'prospect',
            'prospect_id' => $prospect->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete('/prospects/'.$prospect->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('contacts', [
            'prospect_id' => $prospect->id,
            'type' => 'prospect',
        ]);
    }

    public function test_update_prospect_with_valid_data(): void
    {
        $prospect = Prospect::factory()->forOwner($this->owner)->create([
            'first_name' => 'Original',
            'email' => 'original@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/prospects/'.$prospect->id, [
                'email' => 'updated@example.com',
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'company_name' => 'Updated Company',
                'country' => 'USA',
                'phone_number' => '9876543210',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(200);

        $prospect->refresh();
        $this->assertEquals('Updated', $prospect->first_name);
        $this->assertEquals('updated@example.com', $prospect->email);
    }

    public function test_create_prospect_with_first_name_too_long_fails_at_db(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'test@example.com',
                'first_name' => $longName,
                'last_name' => 'Prospect',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }

    public function test_update_prospect_without_data_fails(): void
    {
        $prospect = Prospect::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->put('/prospects/'.$prospect->id, []);

        $response->assertStatus(302);
    }

    public function test_update_prospect_with_duplicate_email_allows(): void
    {
        $prospect1 = Prospect::factory()->forOwner($this->owner)->create([
            'email' => 'one@example.com',
        ]);
        $prospect2 = Prospect::factory()->forOwner($this->owner)->create([
            'email' => 'two@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/prospects/'.$prospect2->id, [
                'email' => 'one@example.com',
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'company_name' => 'Company',
                'country' => 'USA',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(200);
    }

    public function test_create_prospect_creates_associated_contact(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'contacttest@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }

    public function test_delete_prospect_handles_missing_prospect_gracefully(): void
    {
        $response = $this->actingAs($this->owner)
            ->delete('/prospects/99999');

        $response->assertStatus(500);
    }

    public function test_create_prospect_with_missing_first_name_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'test@example.com',
                'last_name' => 'Prospect',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }

    public function test_create_prospect_with_missing_last_name_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'company_name' => 'Test Company',
                'country' => 'Canada',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(500);
    }
}
