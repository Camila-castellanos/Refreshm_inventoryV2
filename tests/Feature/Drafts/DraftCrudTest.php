<?php

namespace Tests\Feature\Drafts;

use App\Models\Draft;
use App\Models\DraftItem;
use Tests\TestCaseWithCompany;

class DraftCrudTest extends TestCaseWithCompany
{
    public function test_index_returns_drafts_json(): void
    {
        Draft::factory()->forOwner($this->owner->id)->create();
        Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Test Draft']);

        $response = $this->actingAs($this->owner)
            ->get('/drafts');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_simple_list_returns_drafts(): void
    {
        Draft::factory()->forOwner($this->owner->id)->create();
        Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Simple Draft']);

        $response = $this->actingAs($this->owner)
            ->get('/drafts/simple');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
    }

    public function test_store_creates_draft(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'New Draft',
            'vendor' => 'Test Vendor',
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'colour' => 'Black',
                    'grade' => 'A',
                    'battery' => '90%',
                    'cost' => 500.00,
                    'selling_price' => 699.99,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
        $response->assertHeader('Content-Type', 'application/json');

        $this->assertDatabaseHas('drafts', [
            'title' => 'New Draft',
            'vendor' => 'Test Vendor',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/drafts', []);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['date', 'title', 'items']);
    }

    public function test_store_updates_existing_draft(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Original Title']);

        $data = [
            'id' => $draft->id,
            'date' => now()->toDateString(),
            'title' => 'Updated Title',
            'vendor' => 'Updated Vendor',
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15', 'cost' => 500],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
        $draft->refresh();
        $this->assertEquals('Updated Title', $draft->title);
    }

    public function test_show_returns_draft_with_items(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/drafts/{$draft->id}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();
        $this->assertArrayHasKey('items', $data);
    }

    public function test_destroy_deletes_draft(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        $response = $this->actingAs($this->owner)
            ->delete("/drafts/{$draft->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('drafts', ['id' => $draft->id]);
    }

    public function test_purge_draft_removes_storage_positions(): void
    {
        $storage = $this->createStorage();
        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        DraftItem::factory()->forDraft($draft->id)->create([
            'storage_id' => $storage->id,
            'storage_position' => 1,
            'location' => 'A1',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("/drafts/purge/{$draft->id}");

        $response->assertStatus(200);

        $draft->load('items');
        foreach ($draft->items as $item) {
            $this->assertNull($item->storage_id);
            $this->assertNull($item->storage_position);
            $this->assertNull($item->location);
        }
    }

    public function test_store_creates_draft_items(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Draft with Items',
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'colour' => 'Black',
                    'grade' => 'A',
                    'cost' => 500.00,
                ],
                [
                    'manufacturer' => 'Samsung',
                    'model' => 'Galaxy S24',
                    'colour' => 'Blue',
                    'grade' => 'B',
                    'cost' => 400.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);

        $draft = Draft::latest('id')->first();
        $this->assertNotNull($draft->items);
        $this->assertCount(2, $draft->items);
    }

    public function test_store_handles_unassigned_items(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Draft with Unassigned Items',
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'draft_unassigned' => true,
                    'storage_id' => 1,
                    'storage_position' => 1,
                ],
                [
                    'manufacturer' => 'Samsung',
                    'model' => 'Galaxy S24',
                    'draft_unassigned' => false,
                    'storage_id' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);

        $draft = Draft::latest('id')->first();
        $unassignedItem = $draft->items()->where('draft_unassigned', true)->first();

        $this->assertNotNull($unassignedItem);
        $this->assertNull($unassignedItem->storage_id);
        $this->assertNull($unassignedItem->storage_position);
    }
}
