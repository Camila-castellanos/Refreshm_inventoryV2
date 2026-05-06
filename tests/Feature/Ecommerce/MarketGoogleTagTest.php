<?php

namespace Tests\Feature\Ecommerce;

use App\Models\Ecommerce\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCaseWithCompany;

class MarketGoogleTagTest extends TestCaseWithCompany
{
    use RefreshDatabase;

    /** @test */
    public function the_markets_table_has_a_google_tag_id_column()
    {
        $this->assertTrue(Schema::hasColumn('markets', 'google_tag_id'));
    }

    /** @test */
    public function google_tag_id_is_fillable_in_market_model()
    {
        $market = new Market(['google_tag_id' => 'G-12345']);
        $this->assertEquals('G-12345', $market->google_tag_id);
    }

    /** @test */
    public function get_safe_data_includes_google_tag_id()
    {
        $shop = $this->createShop();
        
        // Case 1: Present
        $market = Market::factory()->forShop($shop->id)->create(['google_tag_id' => 'G-ABCDE']);
        $safeData = $market->getSafeData();
        
        $this->assertArrayHasKey('google_tag_id', $safeData);
        $this->assertEquals('G-ABCDE', $safeData['google_tag_id']);

        // Case 2: Null
        $market2 = Market::factory()->forShop($shop->id)->create(['google_tag_id' => null]);
        $safeData2 = $market2->getSafeData();
        $this->assertArrayHasKey('google_tag_id', $safeData2);
        $this->assertNull($safeData2['google_tag_id']);
    }

    /** @test */
    public function google_tag_id_is_validated_and_saved_on_store()
    {
        $shop = $this->createShop();

        $data = [
            'name' => 'Test Market GTAG',
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'contact_email' => 'gtag@market.com',
            'google_tag_id' => 'G-STORE-123',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/ecommerce/markets', $data);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('markets', [
            'name' => 'Test Market GTAG',
            'google_tag_id' => 'G-STORE-123',
        ]);
    }

    /** @test */
    public function google_tag_id_is_validated_and_saved_on_update()
    {
        $shop = $this->createShop();
        $market = Market::factory()->forShop($shop->id)->create([
            'google_tag_id' => 'OLD-TAG',
        ]);

        $data = [
            'name' => $market->name,
            'shop_id' => $shop->id,
            'currency' => $market->currency,
            'contact_email' => $market->contact_email,
            'google_tag_id' => 'G-UPDATED-456',
        ];

        $response = $this->actingAs($this->owner)
            ->put("/ecommerce/markets/{$market->id}", $data);

        $response->assertStatus(302);
        
        $market->refresh();
        $this->assertEquals('G-UPDATED-456', $market->google_tag_id);
    }

    /** @test */
    public function google_tag_id_must_be_at_most_50_characters()
    {
        $shop = $this->createShop();
        $longTag = str_repeat('G', 51);

        $data = [
            'name' => 'Test Market Long GTAG',
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'contact_email' => 'long@market.com',
            'google_tag_id' => $longTag,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/ecommerce/markets', $data);

        $response->assertSessionHasErrors(['google_tag_id']);
    }

    /** @test */
    public function google_tag_id_must_follow_standard_format()
    {
        $shop = $this->createShop();

        $invalidTags = [
            '<script>alert(1)</script>',
            'G-123!@#',
            'javascript:void(0)',
            'G 12345', // No spaces allowed
        ];

        foreach ($invalidTags as $tag) {
            $data = [
                'name' => 'Test Market Invalid GTAG',
                'shop_id' => $shop->id,
                'currency' => 'USD',
                'contact_email' => 'invalid@market.com',
                'google_tag_id' => $tag,
            ];

            $response = $this->actingAs($this->owner)
                ->post('/ecommerce/markets', $data);

            $response->assertSessionHasErrors(['google_tag_id'], "Failed to reject invalid GTAG: {$tag}");
        }
    }

    /** @test */
    public function google_tag_id_is_nullable()
    {
        $shop = $this->createShop();

        $data = [
            'name' => 'Test Market Null GTAG',
            'shop_id' => $shop->id,
            'currency' => 'USD',
            'contact_email' => 'null@market.com',
            'google_tag_id' => null,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/ecommerce/markets', $data);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('markets', [
            'name' => 'Test Market Null GTAG',
            'google_tag_id' => null,
        ]);
    }
}
