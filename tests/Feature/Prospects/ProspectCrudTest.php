<?php

namespace Tests\Feature\Prospects;

use App\Models\Prospect;
use Tests\TestCaseWithCompany;

class ProspectCrudTest extends TestCaseWithCompany
{
    public function test_can_create_prospect(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/prospects', [
                'email' => 'testprospect@example.com',
                'first_name' => 'Test',
                'last_name' => 'Prospect',
                'company_name' => 'Test Company',
                'city' => 'Toronto',
                'state' => 'Ontario',
                'country' => 'Canada',
                'address' => '123 Test St',
                'phone_number' => '1234567890',
                'contact_type' => 'lead',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('prospects', [
            'email' => 'testprospect@example.com',
            'first_name' => 'Test',
        ]);
    }

    public function test_can_view_prospects_list(): void
    {
        Prospect::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/prospects');

        $response->assertStatus(200);
    }

    public function test_can_view_prospect(): void
    {
        $prospect = Prospect::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/prospects/'.$prospect->id);

        $response->assertStatus(200);
    }

    public function test_can_view_prospect_edit_form(): void
    {
        $prospect = Prospect::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/prospects/'.$prospect->id.'/edit');

        $response->assertStatus(200);
    }

    public function test_can_delete_prospect(): void
    {
        $prospect = Prospect::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->delete('/prospects/'.$prospect->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('prospects', ['id' => $prospect->id]);
    }

    public function test_prospects_require_authentication(): void
    {
        $response = $this->get('/prospects');

        $response->assertRedirect('/login');
    }
}
