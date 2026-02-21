<?php

namespace Tests\Feature\Utilities;

use App\Models\Draft;
use App\Models\DraftItem;
use Tests\TestCaseWithCompany;

class UtilitiesTest extends TestCaseWithCompany
{
    public function test_find_position_page_returns_view(): void
    {
        $storage = $this->createStorage();

        $response = $this->actingAs($this->owner)
            ->get('/utilities/find-position');

        $response->assertStatus(200);
    }

    public function test_search_position_requires_storage_id(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'position' => 1,
            ]);

        $response->assertStatus(302);
    }

    public function test_search_position_requires_position(): void
    {
        $storage = $this->createStorage();

        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'storage_id' => $storage->id,
            ]);

        $response->assertStatus(302);
    }

    public function test_search_position_returns_not_found(): void
    {
        $storage = $this->createStorage();

        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'storage_id' => $storage->id,
                'position' => 999,
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertFalse($data['found']);
        $this->assertEquals(0, $data['count']);
        $this->assertStringContainsString('No item found', $data['message']);
    }

    public function test_search_position_finds_inventory_item(): void
    {
        $storage = $this->createStorage();

        $item = $this->createItem([
            'storage_id' => $storage->id,
            'position' => 1,
            'sold' => null,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'storage_id' => $storage->id,
                'position' => 1,
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['found']);
        $this->assertEquals(1, $data['count']);
        $this->assertEquals('inventory', $data['results'][0]['type']);
    }

    public function test_search_position_finds_draft_item(): void
    {
        $storage = $this->createStorage();

        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        DraftItem::factory()->forDraft($draft->id)->create([
            'storage_id' => $storage->id,
            'storage_position' => 5,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'storage_id' => $storage->id,
                'position' => 5,
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['found']);
        $this->assertEquals(1, $data['count']);
        $this->assertEquals('draft', $data['results'][0]['type']);
    }

    public function test_search_position_handles_duplicates(): void
    {
        $storage = $this->createStorage();

        $this->createItem([
            'storage_id' => $storage->id,
            'position' => 10,
            'sold' => null,
        ]);

        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create([
            'storage_id' => $storage->id,
            'storage_position' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'storage_id' => $storage->id,
                'position' => 10,
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['found']);
        $this->assertEquals(2, $data['count']);
        $this->assertStringContainsString('duplicated location', $data['message']);
    }

    public function test_search_position_excludes_sold_items(): void
    {
        $storage = $this->createStorage();

        $this->createItem([
            'storage_id' => $storage->id,
            'position' => 15,
            'sold' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/utilities/search-position', [
                'storage_id' => $storage->id,
                'position' => 15,
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertFalse($data['found']);
        $this->assertEquals(0, $data['count']);
    }
}
