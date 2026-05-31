<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaDataSharingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shares_customer_currency_in_inertia_props()
    {
        $user = \App\Models\User::factory()->create();

        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'Test Customer',
            'email' => 'test@example.com',
            'currency' => 'USD',
            'user_id' => $user->id,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('public-store.portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('customer_auth.user.currency', 'USD')
            );
    }

    /** @test */
    public function it_shares_null_currency_if_not_set()
    {
        $user = \App\Models\User::factory()->create();

        $customer = Customer::withoutGlobalScopes()->create([
            'customer' => 'Test Customer',
            'email' => 'test@example.com',
            'currency' => null,
            'user_id' => $user->id,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('public-store.portal.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('customer_auth.user.currency', null)
            );
    }
}
