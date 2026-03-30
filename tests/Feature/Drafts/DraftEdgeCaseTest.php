<?php

namespace Tests\Feature\Drafts;

use App\Models\Draft;
use App\Models\DraftItem;
use App\Models\User;
use Tests\TestCaseWithCompany;

class DraftEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_draft_without_title_fails(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('title');
    }

    public function test_create_draft_with_title_too_long_fails(): void
    {
        $longTitle = str_repeat('A', 256);

        $data = [
            'date' => now()->toDateString(),
            'title' => $longTitle,
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('title');
    }

    public function test_create_draft_without_date_fails(): void
    {
        $data = [
            'title' => 'Test Draft',
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('date');
    }

    public function test_create_draft_without_items_fails(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Test Draft',
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('items');
    }

    public function test_create_draft_with_empty_items_array_fails(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Test Draft',
            'items' => [],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('items');
    }

    public function test_create_draft_with_large_number_of_items(): void
    {
        $items = [];
        for ($i = 0; $i < 150; $i++) {
            $items[] = [
                'manufacturer' => 'Apple',
                'model' => 'iPhone 15',
                'cost' => 500,
            ];
        }

        $data = [
            'date' => now()->toDateString(),
            'title' => 'Large Draft',
            'items' => $items,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
    }

    public function test_update_nonexistent_draft_creates_new_one(): void
    {
        $data = [
            'id' => 99999,
            'date' => now()->toDateString(),
            'title' => 'New Draft',
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('drafts', ['title' => 'New Draft']);
    }

    public function test_update_draft_of_another_user_allows(): void
    {
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);

        $otherDraft = Draft::factory()->forOwner($otherUser->id)->create([
            'title' => 'Original Title',
        ]);

        $data = [
            'id' => $otherDraft->id,
            'date' => now()->toDateString(),
            'title' => 'Updated Title',
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
    }

    public function test_purge_draft_without_items_succeeds(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        $response = $this->actingAs($this->owner)
            ->post("/drafts/purge/{$draft->id}");

        $response->assertStatus(200);
    }

    public function test_purge_nonexistent_draft_returns_404(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/drafts/purge/99999');

        $response->assertStatus(404);
    }

    public function test_create_draft_with_vendor_too_long_fails(): void
    {
        $longVendor = str_repeat('V', 256);

        $data = [
            'date' => now()->toDateString(),
            'title' => 'Test Draft',
            'vendor' => $longVendor,
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('vendor');
    }

    public function test_update_draft_clears_old_items(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->create(['model' => 'Old Model']);
        DraftItem::factory()->forDraft($draft->id)->create(['model' => 'Another Old']);

        $data = [
            'id' => $draft->id,
            'date' => now()->toDateString(),
            'title' => 'Updated Draft',
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'New Model'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);

        $draft->load('items');
        $this->assertCount(1, $draft->items);
        $this->assertEquals('New Model', $draft->items->first()->model);
    }

    public function test_create_draft_with_null_item_values_allows(): void
    {
        $data = [
            'date' => now()->toDateString(),
            'title' => 'Test Draft',
            'items' => [
                [
                    'manufacturer' => null,
                    'model' => null,
                    'colour' => null,
                    'grade' => null,
                    'cost' => null,
                    'selling_price' => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
    }

    public function test_delete_draft_with_many_items(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();

        for ($i = 0; $i < 50; $i++) {
            DraftItem::factory()->forDraft($draft->id)->create();
        }

        $this->assertDatabaseHas('draft_items', ['draft_id' => $draft->id]);

        $response = $this->actingAs($this->owner)
            ->delete("/drafts/{$draft->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('drafts', ['id' => $draft->id]);
        $this->assertDatabaseMissing('draft_items', ['draft_id' => $draft->id]);
    }

    public function test_create_draft_with_max_length_title(): void
    {
        $maxTitle = str_repeat('A', 255);

        $data = [
            'date' => now()->toDateString(),
            'title' => $maxTitle,
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('drafts', ['title' => $maxTitle]);
    }

    public function test_simple_list_returns_correct_format(): void
    {
        Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Draft 1']);
        Draft::factory()->forOwner($this->owner->id)->create(['title' => 'Draft 2']);

        $response = $this->actingAs($this->owner)
            ->get('/drafts/simple');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data[0] ?? []);
        $this->assertArrayHasKey('title', $data[0] ?? []);
        $this->assertArrayHasKey('created_at', $data[0] ?? []);
    }

    public function test_create_draft_with_invalid_date_format_fails(): void
    {
        $data = [
            'date' => 'not-a-date',
            'title' => 'Test Draft',
            'items' => [
                ['manufacturer' => 'Apple', 'model' => 'iPhone 15'],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/drafts', $data);

        $response->assertSessionHasErrors('date');
    }

    public function test_purge_draft_preserves_items_count(): void
    {
        $draft = Draft::factory()->forOwner($this->owner->id)->create();
        DraftItem::factory()->forDraft($draft->id)->count(5)->create();

        $response = $this->actingAs($this->owner)
            ->post("/drafts/purge/{$draft->id}");

        $response->assertStatus(200);

        $draft->load('items');
        $this->assertCount(5, $draft->items);
    }
}
