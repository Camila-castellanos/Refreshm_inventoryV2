<?php

namespace Tests\Feature\Vendors;

use App\Models\Bill;
use App\Models\Item;
use App\Models\Vendor;
use Tests\TestCaseWithCompany;

class VendorEdgeCaseTest extends TestCaseWithCompany
{
    public function test_vendor_without_name_fails_validation(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => '',
                'first_name' => ['John'],
                'last_name' => ['Doe'],
            ]);

        $response->assertSessionHasErrors('vendor');
    }

    public function test_vendor_with_name_too_long_fails_validation(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => $longName,
            ]);

        $response->assertSessionHasErrors('vendor');
    }

    public function test_vendor_with_invalid_email_format(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Test Vendor',
                'email' => ['not-an-email'],
            ]);

        $response->assertStatus(201);
    }

    public function test_vendor_with_duplicate_name_allows_creation(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create([
            'vendor' => 'Duplicate Name Vendor',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Duplicate Name Vendor',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vendors', [
            'vendor' => 'Duplicate Name Vendor',
        ]);
    }

    public function test_vendor_with_duplicate_email_allows_creation(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create([
            'vendor' => 'Vendor One',
            'email' => ['same@email.com'],
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Vendor Two',
                'email' => ['same@email.com'],
            ]);

        $response->assertStatus(201);
    }

    public function test_vendor_with_invalid_website_allows_creation(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Test Vendor',
                'website' => 'not-a-valid-url',
            ]);

        $response->assertStatus(201);
    }

    public function test_vendor_with_very_long_phone_allows_creation(): void
    {
        $longPhone = str_repeat('1', 256);

        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Test Vendor',
                'phone' => [$longPhone],
            ]);

        $response->assertStatus(201);
    }

    public function test_vendor_with_only_required_field_succeeds(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => 'Minimal Vendor',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vendors', [
            'vendor' => 'Minimal Vendor',
        ]);
    }

    public function test_vendor_update_with_empty_name_fails_validation(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->put('/vendor/'.$vendor->id, [
                'vendor' => '',
            ]);

        $response->assertSessionHasErrors('vendor');
    }

    public function test_delete_vendor_with_items_associated(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete('/vendor/'.$vendor->id);

        $response->assertStatus(200);
    }

    public function test_delete_vendor_with_bills_associated(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        Bill::factory()->forOwner($this->owner)->create([
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete('/vendor/'.$vendor->id);

        $response->assertStatus(200);
    }

    public function test_vendor_list_search_with_empty_query(): void
    {
        Vendor::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/vendor/list?search=');

        $response->assertStatus(200);
    }

    public function test_vendor_list_search_with_special_characters(): void
    {
        Vendor::factory()->forOwner($this->owner)->create([
            'vendor' => 'Test @#$% Vendor',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/vendor/list?search=@#$%');

        $response->assertStatus(200);
    }

    public function test_vendor_list_search_nonexistent_returns_empty(): void
    {
        Vendor::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/vendor/list?search=NonExistentVendor12345');

        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    public function test_vendor_update_maintains_associations(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        $item = Item::factory()->create([
            'user_id' => $this->owner->id,
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/vendor/'.$vendor->id, [
                'vendor' => 'Updated Vendor Name',
                'first_name' => ['New'],
                'last_name' => ['Name'],
                'email' => ['new@vendor.com'],
                'phone' => ['5551234567'],
                'currency' => 'CAD',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'vendor' => 'Updated Vendor Name',
        ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_vendor_create_with_max_length_name_succeeds(): void
    {
        $maxLengthName = str_repeat('A', 255);

        $response = $this->actingAs($this->owner)
            ->post('/vendor/store', [
                'vendor' => $maxLengthName,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('vendors', [
            'vendor' => $maxLengthName,
        ]);
    }
}
