<?php

namespace Tests\Feature\PublicStore;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_records_a_visit_when_a_logged_in_customer_views_a_shop()
    {
        // GIVEN
        $customer = Customer::factory()->create();
        $shop = Shop::factory()->create();

        $this->assertDatabaseCount('customer_visited_shops', 0);

        // WHEN
        $this->actingAs($customer, 'customer')
            ->get(route('public.inventory.shop.index', ['shopSlug' => $shop->slug]));

        // THEN
        $this->assertDatabaseHas('customer_visited_shops', [
            'customer_id' => $customer->id,
            'shop_id' => $shop->id,
        ]);
    }

    /** @test */
    public function it_updates_last_visited_at_on_subsequent_visits()
    {
        // GIVEN
        $customer = Customer::factory()->create();
        $shop = Shop::factory()->create();
        
        $yesterday = now()->subDay()->startOfMinute();
        
        DB::table('customer_visited_shops')->insert([
            'customer_id' => $customer->id,
            'shop_id' => $shop->id,
            'last_visited_at' => $yesterday,
        ]);

        // WHEN
        $this->actingAs($customer, 'customer')
            ->get(route('public.inventory.shop.index', ['shopSlug' => $shop->slug]));

        // THEN
        $record = DB::table('customer_visited_shops')
            ->where('customer_id', $customer->id)
            ->where('shop_id', $shop->id)
            ->first();

        $this->assertTrue(now()->diffInSeconds($record->last_visited_at) < 5);
        $this->assertNotEquals($yesterday->toDateTimeString(), $record->last_visited_at);
    }

    /** @test */
    public function it_does_not_track_guest_visits()
    {
        // GIVEN
        $shop = Shop::factory()->create();

        // WHEN
        $this->get(route('public.inventory.shop.index', ['shopSlug' => $shop->slug]));

        // THEN
        $this->assertDatabaseCount('customer_visited_shops', 0);
    }
}
