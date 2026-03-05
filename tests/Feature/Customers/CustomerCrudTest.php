<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use Tests\TestCaseWithCompany;

class CustomerCrudTest extends TestCaseWithCompany
{
    public function test_can_view_customers_list(): void
    {
        Customer::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/customer');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('customers'));
    }

    public function test_can_view_customer_create_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/customer/create');

        $response->assertStatus(200);
    }

    public function test_can_create_customer(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'customer_name' => 'John Doe',
                'first_name' => ['John'],
                'last_name' => ['Doe'],
                'email' => ['john@example.com'],
                'personal_phone' => ['1234567890'],
                'credit' => 100.00,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'customer' => 'John Doe',
            'credit' => 100.00,
        ]);
    }

    public function test_can_create_customer_with_full_details(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'customer_name' => 'Jane Smith',
                'first_name' => ['Jane'],
                'last_name' => ['Smith'],
                'email' => ['jane@example.com'],
                'personal_phone' => ['9876543210'],
                'accnumber' => 'ACC-12345',
                'website' => 'https://example.com',
                'note' => 'Test customer notes',
                'billing_currency' => 'USD',
                'credit' => 500.00,
                'billing_address' => '123 Main St',
                'billing_country' => 'USA',
                'billing_state' => 'NY',
                'billing_city' => 'New York',
                'billing_postal_code' => '10001',
                'shipto' => 'Jane Smith',
                'shipping_address' => '456 Shipping Ave',
                'shipping_country' => 'USA',
                'shipping_state' => 'NY',
                'shipping_city' => 'New York',
                'shipping_postal_code' => '10002',
                'shipping_phone' => '5551234567',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'customer' => 'Jane Smith',
            'credit' => 500.00,
            'account_number' => 'ACC-12345',
            'website' => 'https://example.com',
            'currency' => 'USD',
        ]);
    }

    public function test_can_view_customer_edit_form(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/customer/'.$customer->id.'/edit');

        $response->assertStatus(200);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'customer' => 'Old Name',
            'credit' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/customer/'.$customer->id, [
                'customer_name' => 'Updated Name',
                'first_name' => ['Updated'],
                'last_name' => ['Name'],
                'email' => ['updated@example.com'],
                'personal_phone' => ['1112223333'],
                'credit' => 0,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'customer' => 'Updated Name',
        ]);
    }

    public function test_can_update_customer_credit(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'credit' => 50.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/customer/'.$customer->id, [
                'customer_name' => $customer->customer,
                'first_name' => [$customer->first_name],
                'last_name' => [$customer->last_name],
                'email' => [$customer->email],
                'personal_phone' => [$customer->phone],
                'credit' => 200.00,
            ]);

        $response->assertStatus(200);

        $customer->refresh();
        $this->assertEquals(200.00, $customer->credit);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete('/customer/'.$customer->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_can_get_customers_list_for_autocomplete(): void
    {
        Customer::factory()->count(3)->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/customer/list?search=test');

        $response->assertStatus(200);
    }

    public function test_can_search_customers_by_name(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'customer' => 'John Smith',
        ]);

        Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'customer' => 'Jane Doe',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/customers/by-name/John Smith');

        $response->assertStatus(200);
        $response->assertJsonFragment(['customer' => 'John Smith']);
    }

    public function test_can_get_customers_by_date_range(): void
    {
        Customer::factory()->count(2)->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/customer/datewise', [
                'startDate' => now()->subMonth()->format('Y-m-d'),
                'endDate' => now()->addDay()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);
    }

    public function test_customers_require_authentication(): void
    {
        $response = $this->get('/customer');

        $response->assertRedirect('/login');
    }

    public function test_create_customer_requires_authentication(): void
    {
        $response = $this->post('/customer', [
            'customer_name' => 'Test',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_delete_customer_requires_authentication(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->delete('/customer/'.$customer->id);

        $response->assertRedirect('/login');
    }

    public function test_can_create_customer_with_optional_phones(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'customer_name' => 'Optional Phone Customer',
                'first_name' => ['John'],
                'last_name' => ['Doe'],
                'email' => ['john@example.com'],
                'personal_phone' => ['1234567890'],
                'personal_phone_optional' => [
                    ['555-111', '555-222'],
                ],
                'credit' => 0,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'customer' => 'Optional Phone Customer',
            'phone_optional' => json_encode([['555-111', '555-222']]),
        ]);
    }

    public function test_can_create_customer_with_multiple_contacts_and_optional_phones(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'customer_name' => 'Multi Contact Customer',
                'first_name' => ['Contact A', 'Contact B'],
                'last_name' => ['Last A', 'Last B'],
                'email' => ['a@example.com', 'b@example.com'],
                'personal_phone' => ['111', '222'],
                'personal_phone_optional' => [
                    ['Tel-A'],
                    ['Tel-B1', 'Tel-B2'],
                ],
                'credit' => 0,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'customer' => 'Multi Contact Customer',
            'phone_optional' => json_encode([['Tel-A'], ['Tel-B1', 'Tel-B2']]),
        ]);
    }

    public function test_can_update_customer_optional_phones(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
            'phone_optional' => json_encode([['Old-Tel']]),
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/customer/'.$customer->id, [
                'customer_name' => $customer->customer,
                'first_name' => [$customer->first_name],
                'last_name' => [$customer->last_name],
                'email' => [$customer->email],
                'personal_phone' => [$customer->phone],
                'personal_phone_optional' => [
                    ['New-Tel-1', 'New-Tel-2'],
                ],
                'credit' => $customer->credit,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone_optional' => json_encode([['New-Tel-1', 'New-Tel-2']]),
        ]);
    }

    public function test_optional_phones_are_cleaned_and_filtered(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'customer_name' => 'Dirty Data Customer',
                'first_name' => ['John'],
                'last_name' => ['Doe'],
                'email' => ['john@example.com'],
                'personal_phone' => ['123'],
                'personal_phone_optional' => [
                    ['Valid', '', '   ', null],
                ],
                'credit' => 0,
            ]);

        $response->assertStatus(201);

        // Should only save the "Valid" one and maintain indices
        $this->assertDatabaseHas('customers', [
            'customer' => 'Dirty Data Customer',
            'phone_optional' => json_encode([['Valid']]),
        ]);
    }

    public function test_phone_optional_is_handled_correctly_when_empty(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'customer_name' => 'Empty Phone Customer',
                'first_name' => ['John'],
                'last_name' => ['Doe'],
                'email' => ['john@example.com'],
                'personal_phone' => ['123'],
                'personal_phone_optional' => [
                    ['', null],
                ],
                'credit' => 0,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('customers', [
            'customer' => 'Empty Phone Customer',
            'phone_optional' => null,
        ]);
    }
}
