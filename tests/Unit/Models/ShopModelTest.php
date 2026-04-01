<?php

namespace Tests\Unit\Models;

use App\Models\Shop;
use Tests\TestCaseWithCompany;

class ShopModelTest extends TestCaseWithCompany
{
    public function test_shop_belongs_to_company(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(\App\Models\Company::class, $shop->company);
    }

    public function test_shop_has_many_items(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->createItem(['shop_id' => $shop->id]);
        $this->createItem(['shop_id' => $shop->id]);

        $this->assertCount(2, $shop->items);
    }

    public function test_shop_casts_public_tabs_to_array(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
            'public_tabs' => ['tab1', 'tab2'],
        ]);

        $this->assertIsArray($shop->public_tabs);
    }

    public function test_shop_generates_slug_on_create(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'My Test Shop',
            'slug' => null,
        ]);

        $this->assertNotNull($shop->slug);
    }

    public function test_shop_find_by_slug_returns_shop(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Find Me Shop',
        ]);

        $found = Shop::findBySlug($shop->slug);

        $this->assertEquals($shop->id, $found->id);
    }

    public function test_shop_find_by_slug_returns_null_for_nonexistent(): void
    {
        $found = Shop::findBySlug('nonexistent-slug');

        $this->assertNull($found);
    }

    public function test_shop_get_route_key_name_returns_slug(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertEquals('slug', $shop->getRouteKeyName());
    }

    public function test_shop_resolve_route_binding_finds_by_slug(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Route Test Shop',
        ]);

        $resolved = $shop->resolveRouteBinding($shop->slug);

        $this->assertEquals($shop->id, $resolved->id);
    }

    public function test_shop_resolve_route_binding_finds_by_id_when_numeric(): void
    {
        $shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $resolved = $shop->resolveRouteBinding($shop->id);

        $this->assertEquals($shop->id, $resolved->id);
    }
}
