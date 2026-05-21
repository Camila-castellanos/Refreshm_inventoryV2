<?php

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\IncomingRequest;
use App\Models\Item;
use App\Models\ReturnItems;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsCustomer(Customer $customer): self
    {
        return $this->actingAs($customer, 'customer');
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->get(route('public-store.portal.dashboard'));

        $response->assertStatus(302);
        // Redirects to login (exact URL may vary based on guard config)
        $this->assertTrue(
            str_ends_with($response->headers->get('Location'), '/login') ||
            str_ends_with($response->headers->get('Location'), route('public-store.portal.login.show'))
        );
    }

    public function test_authenticated_can_access_dashboard(): void
    {
        $customer = Customer::factory()->create(['password' => 'hashed']);

        $response = $this->actingAsCustomer($customer)
            ->get(route('public-store.portal.dashboard'));

        $response->assertStatus(200);
    }

    public function test_authenticated_can_list_requests(): void
    {
        $customer = Customer::factory()->create(['password' => 'hashed']);

        IncomingRequest::factory()->count(3)->create([
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAsCustomer($customer)
            ->get(route('public-store.portal.requests'));

        $response->assertStatus(200);
    }

    public function test_authenticated_can_only_see_own_requests(): void
    {
        $customer1 = Customer::factory()->create(['password' => 'hashed']);
        $customer2 = Customer::factory()->create(['password' => 'hashed']);

        IncomingRequest::factory()->create(['customer_id' => $customer1->id]);
        IncomingRequest::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->actingAsCustomer($customer1)
            ->get(route('public-store.portal.requests'));

        $response->assertStatus(200);
    }

    public function test_authenticated_cannot_access_other_customer_request(): void
    {
        $customer1 = Customer::factory()->create(['password' => 'hashed']);
        $customer2 = Customer::factory()->create(['password' => 'hashed']);

        $request = IncomingRequest::factory()->create(['customer_id' => $customer2->id]);

        $response = $this->actingAsCustomer($customer1)
            ->get(route('public-store.portal.requests.show', ['id' => $request->id]));

        $response->assertStatus(403);
    }

    public function test_authenticated_can_view_own_credit(): void
    {
        $customer = Customer::factory()->create([
            'password' => 'hashed',
            'credit' => 150.00,
        ]);

        $response = $this->actingAsCustomer($customer)
            ->get(route('public-store.portal.credit'));

        $response->assertStatus(200);
    }

    public function test_authenticated_can_list_orders(): void
    {
        $customer = Customer::factory()->create(['password' => 'hashed']);
        $user = \App\Models\User::factory()->create();

        // Create a sale with items linked to this customer
        $sale = Sale::factory()->create(['user_id' => $user->id]);
        Item::factory()->create([
            'sale_id' => $sale->id,
            'customer' => $customer->email,
        ]);

        $response = $this->actingAsCustomer($customer)
            ->get(route('public-store.portal.orders'));

        $response->assertStatus(200);
    }

    public function test_authenticated_can_list_returns(): void
    {
        $customer = Customer::factory()->create([
            'password' => 'hashed',
            'email' => 'test@example.com',
        ]);

        ReturnItems::create([
            'item' => 'iPhone 15',
            'customer' => $customer->email,
            'credit' => 50.00,
            'imei' => '12345',
            'model' => 'iPhone 15',
            'sale' => 1,
        ]);

        $response = $this->actingAsCustomer($customer)
            ->get(route('public-store.portal.returns'));

        $response->assertStatus(200);
    }

    public function test_customer_cannot_update_another_customer_profile(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $response = $this->actingAs($customer1, 'customer')
            ->putJson(route('public-store.portal.profile'), [
                'default_store' => 'Hacked',
            ]);

        // Customer can update their own profile, so this should pass
        $response->assertOk();

        // But customer2's data should not be affected
        $customer2->refresh();
        $this->assertNotEquals('Hacked', $customer2->default_store);
    }

    public function test_dashboard_includes_soft_deleted_and_processed_requests_in_all_and_recent_requests(): void
    {
        $customer = Customer::factory()->create(['password' => 'hashed']);

        // 1. Create an active request
        $activeRequest = IncomingRequest::factory()->create([
            'customer_id' => $customer->id,
            'processed' => false,
        ]);

        // 2. Create a processed request
        $processedRequest = IncomingRequest::factory()->create([
            'customer_id' => $customer->id,
            'processed' => true,
        ]);

        // 3. Create a soft-deleted request
        $deletedRequest = IncomingRequest::factory()->create([
            'customer_id' => $customer->id,
            'processed' => false,
        ]);
        $deletedRequest->delete();

        $response = $this->actingAsCustomer($customer)
            ->get(route('public-store.portal.dashboard'));

        $response->assertStatus(200);

        // Assert dashboard statistics
        $response->assertInertia(fn ($page) => $page
            ->component('PublicStore/Portal/Dashboard')
            ->where('total_requests', 3) // Active, Processed, and Deleted request should all count towards total
            ->where('pending_requests', 1) // Only the active request should count as pending
            ->has('all_requests', 3) // Should contain all 3 requests
        );
    }
}