<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use Tests\TestCaseWithCompany;

class MarketAdminCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_markets_for_company(): void
    {
        $shop = $this->createShop();

        Market::factory()->forShop($shop->id)->create();

        $response = $this->actingAs($this->owner)
            ->get('/ecommerce/markets');

        $response->assertStatus(200);
    }

    public function test_create_returns_create_page(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/ecommerce/markets/create');

        $response->assertStatus(200);
    }

    public function test_store_creates_market(): void
    {
        $shop = $this->createShop();

        $data = [
            'name' => 'Test Market',
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'contact_email' => 'test@market.com',
            'is_active' => true,
            'show_inventory_count' => false,
            'description' => 'Test description',
            'tagline' => 'Test tagline',
            'contact_phone' => '1234567890',
            'address' => 'Test address',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/ecommerce/markets', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('markets', [
            'name' => 'Test Market',
            'shop_id' => $shop->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/ecommerce/markets', []);

        $response->assertSessionHasErrors(['name', 'shop_id', 'currency']);
    }

    public function test_store_generates_unique_slug(): void
    {
        $shop = $this->createShop();

        Market::factory()->forShop($shop->id)->create([
            'name' => 'Test Market',
            'slug' => 'test-market',
        ]);

        $data = [
            'name' => 'Test Market',
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'contact_email' => 'test@market.com',
            'description' => 'Test description',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/ecommerce/markets', $data);

        $response->assertStatus(302);

        $market = Market::where('name', 'Test Market')->latest('id')->first();
        $this->assertEquals('test-market-1', $market->slug);
    }

    public function test_edit_returns_edit_page(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/ecommerce/markets/{$market->id}/edit");

        $response->assertStatus(200);
    }

    public function test_update_modifies_market(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop->id)->create([
            'name' => 'Original Name',
        ]);

        $data = [
            'name' => 'Updated Name',
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'contact_email' => 'updated@market.com',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->owner)
            ->put("/ecommerce/markets/{$market->id}", $data);

        $response->assertStatus(302);

        $market->refresh();
        $this->assertEquals('Updated Name', $market->name);
    }

    public function test_destroy_deletes_market(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop->id)->create();
        $marketId = $market->id;

        $response = $this->actingAs($this->owner)
            ->delete("/ecommerce/markets/{$market->id}");

        $response->assertStatus(302);

        $this->assertDatabaseMissing('markets', ['id' => $marketId]);
    }
}
