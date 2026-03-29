<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Item;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->owner = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'OWNER',
        ]);
        $this->storage = Storage::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    protected function createItems(int $count, array $overrides = []): \Illuminate\Database\Eloquent\Collection
    {
        return Item::factory()->count($count)->create(array_merge([
            'user_id' => $this->owner->id,
            'storage_id' => $this->storage->id,
        ], $overrides));
    }

    public function test_list_items_returns_200(): void
    {
        $this->createItems(5);

        $response = $this->getJson('/api/items');

        $response->assertStatus(200);
    }

    public function test_list_items_returns_pagination_structure(): void
    {
        $this->createItems(20);

        $response = $this->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
                'first_page_url',
                'last_page_url',
                'next_page_url',
                'prev_page_url',
            ]);
    }

    public function test_list_items_returns_default_fields(): void
    {
        $this->createItems(3);

        $response = $this->getJson('/api/items');

        $response->assertStatus(200);

        $item = $response->json('data.0');
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('type', $item);
        $this->assertArrayHasKey('model', $item);
        $this->assertArrayHasKey('colour', $item);
        $this->assertArrayHasKey('battery', $item);
        $this->assertArrayHasKey('grade', $item);
        $this->assertArrayHasKey('cost', $item);
        $this->assertArrayHasKey('imei', $item);
        $this->assertArrayHasKey('selling_price', $item);
    }

    public function test_list_items_default_pagination_is_15(): void
    {
        $this->createItems(20);

        $response = $this->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJson([
                'per_page' => 15,
            ]);
    }

    public function test_list_items_custom_pagination(): void
    {
        $this->createItems(20);

        $response = $this->getJson('/api/items?per_page=5');

        $response->assertStatus(200)
            ->assertJson([
                'per_page' => 5,
            ]);

        $response = $this->getJson('/api/items?per_page=50');

        $response->assertStatus(200)
            ->assertJson([
                'per_page' => 50,
            ]);
    }

    public function test_list_items_returns_items_array(): void
    {
        $items = $this->createItems(3);

        $response = $this->getJson('/api/items');

        $response->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_filter_by_type_exact_match(): void
    {
        $this->createItems(3, ['type' => 'device']);
        $this->createItems(2, ['type' => 'accessory']);

        $response = $this->getJson('/api/items?filter[type]=device');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertEquals('device', $item['type']);
        }
    }

    public function test_filter_by_manufacturer_partial_match(): void
    {
        $this->createItems(3, ['manufacturer' => 'Apple']);
        $this->createItems(2, ['manufacturer' => 'Samsung']);

        $response = $this->getJson('/api/items?filter[manufacturer]=App&fields=id,type,model,manufacturer');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertStringContainsString('App', $item['manufacturer']);
        }
    }

    public function test_filter_by_model_partial_match(): void
    {
        $this->createItems(3, ['model' => 'iPhone 15']);
        $this->createItems(2, ['model' => 'Galaxy S24']);

        $response = $this->getJson('/api/items?filter[model]=iPhone');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertNotEmpty($items);
    }

    public function test_filter_by_grade_exact_match(): void
    {
        $this->createItems(3, ['grade' => 'A']);
        $this->createItems(2, ['grade' => 'B']);

        $response = $this->getJson('/api/items?filter[grade]=A');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertEquals('A', $item['grade']);
        }
    }

    public function test_filter_sold_1_returns_sold_items(): void
    {
        $this->createItems(3, ['sold' => now()]);
        $this->createItems(2, ['sold' => null]);

        $response = $this->getJson('/api/items?filter[sold]=1');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(3, $items);
    }

    public function test_filter_sold_0_returns_unsold_items(): void
    {
        $this->createItems(3, ['sold' => now()]);
        $this->createItems(2, ['sold' => null]);

        $response = $this->getJson('/api/items?filter[sold]=0');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(2, $items);
    }

    public function test_filter_sold_true_returns_sold_items(): void
    {
        $this->createItems(3, ['sold' => now()]);
        $this->createItems(2, ['sold' => null]);

        $response = $this->getJson('/api/items?filter[sold]=true');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(3, $items);
    }

    public function test_filter_hold_1_returns_held_items(): void
    {
        $this->createItems(3, ['hold' => now(), 'customer' => 'Test Customer']);
        $this->createItems(2, ['hold' => null, 'customer' => null]);

        $response = $this->getJson('/api/items?filter[hold]=1');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(3, $items);
    }

    public function test_filter_hold_0_returns_unheld_items(): void
    {
        $this->createItems(3, ['hold' => now(), 'customer' => 'Test Customer']);
        $this->createItems(2, ['hold' => null, 'customer' => null]);

        $response = $this->getJson('/api/items?filter[hold]=0');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(2, $items);
    }

    public function test_fields_selection_specific_fields(): void
    {
        $this->createItems(3);

        $response = $this->getJson('/api/items?fields=id,model,cost');

        $response->assertStatus(200);

        $item = $response->json('data.0');
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('model', $item);
        $this->assertArrayHasKey('cost', $item);
    }

    public function test_fields_selection_single_field(): void
    {
        $this->createItems(3);

        $response = $this->getJson('/api/items?fields=id,model');

        $response->assertStatus(200);

        $item = $response->json('data.0');
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('model', $item);
    }

    public function test_multiple_filters_combined(): void
    {
        $this->createItems(3, ['type' => 'device', 'manufacturer' => 'Apple', 'grade' => 'A']);
        $this->createItems(2, ['type' => 'device', 'manufacturer' => 'Samsung', 'grade' => 'B']);
        $this->createItems(1, ['type' => 'accessory', 'manufacturer' => 'Apple', 'grade' => 'A']);

        $response = $this->getJson('/api/items?filter[type]=device&filter[manufacturer]=Apple&fields=id,type,model,manufacturer');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertEquals('device', $item['type']);
            $this->assertStringContainsString('Apple', $item['manufacturer']);
        }
    }

    public function test_empty_result_returns_empty_array(): void
    {
        $response = $this->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
                'total' => 0,
            ]);
    }

    public function test_pagination_metadata_correct(): void
    {
        $this->createItems(25);

        $response = $this->getJson('/api/items?per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'per_page' => 10,
                'total' => 25,
                'current_page' => 1,
                'last_page' => 3,
            ]);
    }

    public function test_second_page_pagination(): void
    {
        $this->createItems(20);

        $response = $this->getJson('/api/items?page=2');

        $response->assertStatus(200)
            ->assertJson([
                'current_page' => 2,
            ]);
    }

    public function test_filter_by_colour_partial_match(): void
    {
        $this->createItems(3, ['colour' => 'Black']);
        $this->createItems(2, ['colour' => 'White']);

        $response = $this->getJson('/api/items?filter[colour]=Blac');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertStringContainsString('Blac', $item['colour']);
        }
    }

    public function test_filter_by_customer_partial_match(): void
    {
        $this->createItems(3, ['customer' => 'John Doe', 'sold' => now()]);
        $this->createItems(2, ['customer' => 'Jane Smith', 'sold' => now()]);

        $response = $this->getJson('/api/items?filter[customer]=John');

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertNotEmpty($items);
    }

    public function test_filter_by_cost_exact_match(): void
    {
        $this->createItems(3, ['cost' => 100.00]);
        $this->createItems(2, ['cost' => 200.00]);

        $response = $this->getJson('/api/items?filter[cost]=100');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertEquals(100, $item['cost']);
        }
    }

    public function test_filter_by_imei_exact_match(): void
    {
        $item = $this->createItems(1)->first();

        $response = $this->getJson('/api/items?filter[imei]='.$item->imei);

        $response->assertStatus(200);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($item->imei, $items[0]['imei']);
    }

    public function test_filter_by_selling_price_exact_match(): void
    {
        $this->createItems(3, ['selling_price' => 299.99]);
        $this->createItems(2, ['selling_price' => 499.99]);

        $response = $this->getJson('/api/items?filter[selling_price]=299.99');

        $response->assertStatus(200);

        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertEquals(299.99, $item['selling_price']);
        }
    }
}
