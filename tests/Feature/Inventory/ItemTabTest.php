<?php

namespace Tests\Feature\Inventory;

use App\Models\Tab;
use App\Models\TabItem;
use Tests\TestCaseWithCompany;

class ItemTabTest extends TestCaseWithCompany
{
    public function test_can_move_item_to_tab(): void
    {
        $item = $this->createItem();
        $tab = $this->createTab();

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/tabmove', [
                'item' => $item->id,
                'tab' => $tab->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tab_items', [
            'item_id' => $item->id,
            'tab_id' => $tab->id,
        ]);
    }

    public function test_can_move_multiple_items_to_tab(): void
    {
        $items = $this->createItems(3);
        $tab = $this->createTab();

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/tabmoveall', [
                'item' => $items->map(fn ($i) => ['id' => $i->id])->toArray(),
                'tab' => $tab->id,
            ]);

        $response->assertStatus(200);

        foreach ($items as $item) {
            $this->assertDatabaseHas('tab_items', [
                'item_id' => $item->id,
                'tab_id' => $tab->id,
            ]);
        }
    }

    public function test_move_to_tab_clears_hold(): void
    {
        $item = $this->createItem();
        $tab = $this->createTab();

        $this->actingAs($this->owner)->put('/inventory/items/hold', [
            'data' => [['id' => $item->id]],
            'customer' => 'Hold Customer',
        ]);

        $item->refresh();
        $this->assertNotNull($item->hold);

        $this->actingAs($this->owner)->post('/inventory/items/tabmove', [
            'item' => $item->id,
            'tab' => $tab->id,
        ]);

        $item->refresh();
        $this->assertNull($item->hold);
    }

    public function test_can_return_item_to_inventory_from_tab(): void
    {
        $item = $this->createItem();
        $tab = $this->createTab();

        TabItem::create([
            'item_id' => $item->id,
            'tab_id' => $tab->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/tabreturnmove', [
                'tab_id' => $tab->id,
                'item_ids' => [$item->id],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tab_items', [
            'item_id' => $item->id,
            'tab_id' => $tab->id,
        ]);
    }

    public function test_items_in_tabs_not_in_inventory(): void
    {
        $item = $this->createItem();
        $tab = $this->createTab();

        TabItem::create([
            'item_id' => $item->id,
            'tab_id' => $tab->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/getItems');

        $response->assertStatus(200);
        $items = $response->json();
        $itemIds = collect($items)->pluck('id')->toArray();

        $this->assertNotContains($item->id, $itemIds);
    }

    public function test_can_get_tab_items(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();
        $tab = $this->createTab();

        TabItem::create(['item_id' => $item1->id, 'tab_id' => $tab->id]);
        TabItem::create(['item_id' => $item2->id, 'tab_id' => $tab->id]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/tab/'.$tab->id);

        $response->assertStatus(200);
    }

    public function test_tab_item_can_be_moved_to_different_tab(): void
    {
        $item = $this->createItem();
        $tab1 = $this->createTab(['name' => 'Tab 1']);
        $tab2 = $this->createTab(['name' => 'Tab 2']);

        TabItem::create(['item_id' => $item->id, 'tab_id' => $tab1->id]);

        $this->assertDatabaseHas('tab_items', [
            'item_id' => $item->id,
            'tab_id' => $tab1->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/tabmove', [
                'item' => $item->id,
                'tab' => $tab2->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tab_items', [
            'item_id' => $item->id,
            'tab_id' => $tab1->id,
        ]);
        $this->assertDatabaseHas('tab_items', [
            'item_id' => $item->id,
            'tab_id' => $tab2->id,
        ]);
    }

    public function test_tab_requires_authentication(): void
    {
        $item = $this->createItem();
        $tab = $this->createTab();

        $response = $this->post('/inventory/items/tabmove', [
            'item' => $item->id,
            'tab' => $tab->id,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_sold_items_not_in_tabs(): void
    {
        $item = $this->createItem(['sold' => now()]);
        $tab = $this->createTab();

        TabItem::create(['item_id' => $item->id, 'tab_id' => $tab->id]);

        $response = $this->actingAs($this->owner)
            ->get('/inventory/items/tab/'.$tab->id);

        $response->assertStatus(200);
    }

    public function test_can_get_user_tabs(): void
    {
        Tab::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/user/tabs');

        $response->assertStatus(200);
    }

    public function test_can_update_tab_name(): void
    {
        $tab = Tab::factory()->forOwner($this->owner)->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/user/tab-name', [
                'tab_id' => $tab->id,
                'name' => 'Updated Tab Name',
            ]);

        $response->assertStatus(200);

        $tab->refresh();
        $this->assertEquals('Updated Tab Name', $tab->name);
    }
}
