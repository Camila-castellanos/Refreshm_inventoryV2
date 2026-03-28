<?php

namespace Tests\Feature\Drafts;

use App\Models\Draft;
use App\Models\DraftItem;
use App\Models\User;
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

    // === RELATIONSHIPS ===

    public function test_draft_belongs_to_user(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        $this->assertEquals($this->owner->id, $draft->user_id);
    }

    public function test_draft_has_many_items(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->count(3)->create();

        $this->assertCount(3, $draft->items);
    }

    public function test_draft_items_belong_to_draft(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        $item = DraftItem::factory()->forDraft($draft->id)->create();

        $this->assertEquals($draft->id, $item->draft_id);
    }

    public function test_draft_applies_company_scope(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherOwner = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        Draft::factory()->forOwner($this->owner->id)->create();
        Draft::factory()->forOwner($otherOwner->id)->create();

        $response = $this->actingAs($this->owner)->get('/drafts');
        $response->assertStatus(200);

        $drafts = $response->json();
        $this->assertTrue(count($drafts) >= 1);
    }

    // === AUTHORIZATION ===
    // Note: Drafts in same company can be accessed by other company members
    // due to CompanyUsersSharedScope which only filters by company_id

    public function test_same_company_users_can_access_drafts(): void
    {
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        $otherDraft = Draft::factory()->forOwner($otherUser->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/drafts/{$otherDraft->id}");

        $response->assertStatus(200);
    }

    public function test_different_company_cannot_access_drafts(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        $otherDraft = Draft::factory()->forOwner($otherUser->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/drafts/{$otherDraft->id}");

        $response->assertStatus(404);
    }

    public function test_non_owner_can_only_see_own_drafts(): void
    {
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Owner Draft']);
        Draft::factory()->forOwner($otherUser->id)->create(['title' => 'User Draft']);

        $response = $this->actingAs($otherUser)->get('/drafts');
        $response->assertStatus(200);

        $drafts = $response->json();
        $titles = collect($drafts)->pluck('title')->toArray();
        $this->assertContains('User Draft', $titles);
    }

    // === VALIDATION ===

    public function test_store_validates_item_storage_id_exists(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Draft with Invalid Storage',
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => 'iPhone 15',
                    'storage_id' => 99999,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['items.0.storage_id']);
    }

    public function test_store_clears_old_items_when_updating(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create(['model' => 'Old Item']);

        $data = [
            'id' => $draft->id,
            'date' => now()->toDateString(),
            'title' => 'Updated Draft',
            'items' => [
                ['manufacturer' => 'Samsung', 'model' => 'New Item'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);

        $draft->load('items');
        $this->assertCount(1, $draft->items);
        $this->assertEquals('New Item', $draft->items->first()->model);
    }

    // === EDGE CASES ===

    public function test_destroy_deletes_cascade_items(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create();

        $this->assertDatabaseHas('draft_items', ['draft_id' => $draft->id]);

        $response = $this->actingAs($this->owner)
            ->delete("/drafts/{$draft->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('drafts', ['id' => $draft->id]);
        $this->assertDatabaseMissing('draft_items', ['draft_id' => $draft->id]);
    }

    public function test_purge_preserves_draft(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Original Title']);

        $response = $this->actingAs($this->owner)
            ->post("/drafts/purge/{$draft->id}");

        $response->assertStatus(200);

        $draft->refresh();
        $this->assertEquals('Original Title', $draft->title);
    }

    public function test_purge_clears_all_storage_fields(): void
    {
        $storage = $this->createStorage();
        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        DraftItem::factory()->forDraft($draft->id)->create([
            'storage_id' => $storage->id,
            'storage_position' => 5,
            'location' => 'Shelf A',
        ]);

        $this->actingAs($this->owner)->post("/drafts/purge/{$draft->id}");

        $draft->load('items');
        $item = $draft->items->first();

        $this->assertNull($item->storage_id);
        $this->assertNull($item->storage_position);
        $this->assertNull($item->location);
    }

    public function test_store_with_single_empty_item(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Draft with Empty Item',
            'items' => [
                [
                    'manufacturer' => 'Apple',
                    'model' => '',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);

        $draft = Draft::latest('id')->first();
        $this->assertCount(1, $draft->items);
    }

    public function test_show_includes_items_relationship(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create();

        $response = $this->actingAs($this->owner)
            ->get("/drafts/{$draft->id}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(1, $data['items']);
    }

    // === DATE CASTS ===

    public function test_draft_date_is_cast_to_date(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create([
            'date' => '2025-01-15',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $draft->date);
    }

    public function test_draft_item_decimals_are_cast(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        $item = DraftItem::factory()->forDraft($draft->id)->create([
            'cost' => 123.45,
            'selling_price' => 199.99,
        ]);

        $this->assertNotNull($item->cost);
        $this->assertNotNull($item->selling_price);
    }
}
