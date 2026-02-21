<?php

namespace Tests\Feature\Shops;

use App\Models\Shop;
use App\Models\Tab;
use Tests\TestCaseWithCompany;

class ShopCrudTest extends TestCaseWithCompany
{
    public function test_can_view_shop(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/shops/'.$shop->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => $shop->name,
        ]);
    }

    public function test_can_update_shop_name(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create([
            'name' => 'Old Shop Name',
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/shops/'.$shop->id, [
                'name' => 'New Shop Name',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('shops', [
            'id' => $shop->id,
            'name' => 'New Shop Name',
        ]);
    }

    public function test_can_update_shop_slug(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create([
            'name' => 'Test Shop',
            'slug' => 'old-slug',
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/shops/'.$shop->id, [
                'name' => 'Test Shop',
                'slug' => 'new-slug',
            ]);

        $response->assertStatus(200);

        $shop->refresh();
        $this->assertEquals('new-slug', $shop->slug);
    }

    public function test_can_update_shop_public_tabs(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create([
            'public_tabs' => [],
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/shops/'.$shop->id, [
                'name' => 'Test Shop',
                'public_tabs' => [1, 2, 3],
            ]);

        $response->assertStatus(200);

        $shop->refresh();
        $this->assertEquals([1, 2, 3], $shop->public_tabs);
    }

    public function test_shop_requires_name(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->put('/shops/'.$shop->id, [
                'name' => '',
            ]);

        $response->assertStatus(302);
    }

    public function test_shop_name_max_length(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create();

        $longName = str_repeat('a', 256);

        $response = $this->actingAs($this->owner)
            ->put('/shops/'.$shop->id, [
                'name' => $longName,
            ]);

        $response->assertStatus(302);
    }

    public function test_can_get_shop_tabs(): void
    {
        $shop = Shop::factory()->forOwner($this->owner)->create();

        Tab::factory()->count(3)->create([
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/shops/'.$shop->id.'/tabs');

        $response->assertStatus(200);
    }

    public function test_unauthorized_cannot_access_other_company_shop(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherUser = \App\Models\User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $shop = Shop::factory()->forOwner($otherUser)->create();

        $response = $this->actingAs($this->owner)
            ->get('/shops/'.$shop->id);

        $response->assertStatus(403);
    }
}
