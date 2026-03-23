<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use Tests\TestCaseWithCompany;

class MarketPublicDisplayTest extends TestCaseWithCompany
{
    public function test_market_public_page_loads_and_displays_info(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop->id)->create([
            'name' => 'Public Market',
            'slug' => 'public-market',
            'is_active' => true,
            'about_us' => ['title' => 'About Us Title', 'content' => 'About Us Content'],
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(200);
        $response->assertSee('Public Market');
        $response->assertSee('About Us Title');
        $response->assertSee('About Us Content');
    }

    public function test_inactive_market_is_not_publicly_accessible(): void
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop->id)->create([
            'slug' => 'inactive-market',
            'is_active' => false,
        ]);

        $response = $this->get("/market/{$market->slug}");

        $response->assertStatus(404);
    }
}
