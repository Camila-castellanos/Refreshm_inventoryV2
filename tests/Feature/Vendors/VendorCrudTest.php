<?php

namespace Tests\Feature\Vendors;

use App\Models\Vendor;
use Tests\TestCaseWithCompany;

class VendorCrudTest extends TestCaseWithCompany
{
    public function test_can_view_vendors_list(): void
    {
        Vendor::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/vendor');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('vendors'));
    }

    public function test_can_view_vendor_create_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/vendor/create');

        $response->assertStatus(200);
    }

    public function test_can_create_vendor(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Test Vendor Inc.',
                'first_name' => ['John'],
                'last_name' => ['Doe'],
                'email' => ['john@testvendor.com'],
                'phone' => ['1234567890'],
                'currency' => 'CAD',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('vendors', [
            'vendor' => 'Test Vendor Inc.',
        ]);
    }

    public function test_can_create_vendor_with_full_details(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Complete Vendor Ltd.',
                'first_name' => ['Jane'],
                'last_name' => ['Smith'],
                'email' => ['jane@completevendor.com'],
                'phone' => ['9876543210'],
                'phone_optional' => [],
                'website' => 'https://completevendor.com',
                'notes' => 'Test notes for vendor',
                'currency' => 'USD',
                'address' => '123 Business St',
                'address_optional' => 'Suite 100',
                'address_country' => 'USA',
                'address_state' => 'NY',
                'address_city' => 'New York',
                'address_postal' => '10001',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('vendors', [
            'vendor' => 'Complete Vendor Ltd.',
            'currency' => 'USD',
            'website' => 'https://completevendor.com',
        ]);
    }

    public function test_can_view_vendor_edit_form(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/vendor/'.$vendor->id.'/edit');

        $response->assertStatus(200);
    }

    public function test_can_update_vendor(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create([
            'vendor' => 'Old Vendor Name',
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/vendor/'.$vendor->id, [
                'vendor' => 'Updated Vendor Name',
                'first_name' => ['New'],
                'last_name' => ['Name'],
                'email' => ['updated@vendor.com'],
                'phone' => ['5551234567'],
                'currency' => 'CAD',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'vendor' => 'Updated Vendor Name',
        ]);
    }

    public function test_can_delete_vendor(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->delete('/vendor/'.$vendor->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('vendors', ['id' => $vendor->id]);
    }

    public function test_can_get_vendors_list_for_autocomplete(): void
    {
        Vendor::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/vendor/list?search=test');

        $response->assertStatus(200);
    }

    public function test_can_get_vendors_by_date_range(): void
    {
        Vendor::factory()->forOwner($this->owner)->count(2)->create();

        $response = $this->actingAs($this->owner)
            ->post('/vendor/datewise', [
                'startDate' => now()->subMonth()->format('Y-m-d'),
                'endDate' => now()->addDay()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);
    }

    public function test_vendors_require_authentication(): void
    {
        $response = $this->get('/vendor');

        $response->assertRedirect('/login');
    }

    public function test_create_vendor_requires_authentication(): void
    {
        $response = $this->post('/vendor/store', [
            'vendor' => 'Test',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_delete_vendor_requires_authentication(): void
    {
        $vendor = Vendor::factory()->create();

        $response = $this->delete('/vendor/'.$vendor->id);

        $response->assertRedirect('/login');
    }
}
