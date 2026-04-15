<?php

namespace Tests\Feature\Inventory;

use App\Models\Item;
use Tests\TestCaseWithCompany;

class GenerateSellingPriceTest extends TestCaseWithCompany
{
    public function test_grade_match_is_exact_and_does_not_match_plus_variants(): void
    {
        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
            'model' => 'iPhone 12 64GB',
            'battery' => '97',
            'grade' => 'B+',
            'issues' => null,
            'selling_price' => 195.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/generate-selling-prices', [
                'items' => [
                    [
                        'model' => 'iPhone 12 64GB',
                        'battery' => '97',
                        'grade' => 'B',
                        'issues' => null,
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('items.0.selling_price', null);
    }

    public function test_model_and_grade_match_exactly_but_are_normalized_by_case_and_spaces(): void
    {
        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
            'model' => 'iPhone 12 64GB',
            'battery' => '95',
            'grade' => 'B',
            'issues' => null,
            'selling_price' => 190.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/generate-selling-prices', [
                'items' => [
                    [
                        'model' => '   iphone 12 64gb   ',
                        'battery' => '95',
                        'grade' => '  b  ',
                        'issues' => null,
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('items.0.selling_price', 190);
    }

    public function test_model_matching_is_exact_and_does_not_match_partial_models(): void
    {
        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
            'model' => 'iPhone 12 64GB Pro',
            'battery' => '95',
            'grade' => 'B',
            'issues' => null,
            'selling_price' => 210.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/generate-selling-prices', [
                'items' => [
                    [
                        'model' => 'iPhone 12 64GB',
                        'battery' => '95',
                        'grade' => 'B',
                        'issues' => null,
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('items.0.selling_price', null);
    }

    public function test_blank_issues_input_is_normalized_to_null_and_matches_null_issues_records(): void
    {
        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
            'model' => 'iPhone 12 64GB',
            'battery' => '95',
            'grade' => 'B',
            'issues' => null,
            'selling_price' => 190.00,
        ]);

        // Should be ignored because incoming blank issues are normalized to null
        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
            'model' => 'iPhone 12 64GB',
            'battery' => '95',
            'grade' => 'B',
            'issues' => 'Screen scratch',
            'selling_price' => 999.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/generate-selling-prices', [
                'items' => [
                    [
                        'model' => 'iPhone 12 64GB',
                        'battery' => '95',
                        'grade' => 'B',
                        'issues' => '   ',
                    ],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('items.0.selling_price', 190);
    }
}
