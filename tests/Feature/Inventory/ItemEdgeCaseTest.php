<?php

namespace Tests\Feature\Inventory;

use App\Models\Item;
use Tests\TestCaseWithCompany;

class ItemEdgeCaseTest extends TestCaseWithCompany
{
    public function test_item_with_duplicate_imei(): void
    {
        $existingItem = Item::factory()->create([
            'user_id' => $this->owner->id,
            'imei' => '123456789012345',
            'storage_id' => $this->storage->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '90%',
                        'cost' => 500.00,
                        'selling_price' => 699.99,
                        'storage_id' => $this->storage->id,
                        'imei' => '123456789012345',
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_zero_selling_price(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '90%',
                        'cost' => 100.00,
                        'selling_price' => 0,
                        'storage_id' => $this->storage->id,
                        'imei' => 'IMEI_ZERO_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_cost_greater_than_selling_price(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'B',
                        'battery' => '85%',
                        'cost' => 500.00,
                        'selling_price' => 300.00,
                        'storage_id' => $this->storage->id,
                        'imei' => 'IMEI_COST_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_without_storage(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '90%',
                        'cost' => 500.00,
                        'selling_price' => 699.99,
                        'imei' => 'IMEI_NOST_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_empty_imei(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '90%',
                        'cost' => 500.00,
                        'selling_price' => 699.99,
                        'storage_id' => $this->storage->id,
                        'imei' => '',
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_different_types(): void
    {
        $types = ['device', 'accessory'];

        foreach ($types as $type) {
            $response = $this->actingAs($this->owner)
                ->post('/inventory/items/update', [
                    'items' => [
                        [
                            'manufacturer' => 'Apple',
                            'model' => 'Sample',
                            'colour' => 'Black',
                            'grade' => $type === 'device' ? 'A' : null,
                            'battery' => $type === 'device' ? '90%' : null,
                            'cost' => 50.00,
                            'selling_price' => 99.99,
                            'storage_id' => $this->storage->id,
                            'imei' => 'IMEI_TYPE_'.$type.'_'.time(),
                            'type' => $type,
                        ],
                    ],
                ]);

            $response->assertStatus(200);
        }
    }

    public function test_item_with_null_grade_for_accessory(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'AirPods Case',
                        'colour' => 'White',
                        'grade' => null,
                        'battery' => null,
                        'cost' => 30.00,
                        'selling_price' => 49.99,
                        'storage_id' => $this->storage->id,
                        'imei' => 'ACC_'.time(),
                        'type' => 'accessory',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_very_long_imei(): void
    {
        $longImei = str_repeat('9', 20);

        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Samsung',
                        'model' => 'Galaxy S24',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '95%',
                        'cost' => 400.00,
                        'selling_price' => 599.99,
                        'storage_id' => $this->storage->id,
                        'imei' => $longImei,
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_without_manufacturer(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '90%',
                        'cost' => 500.00,
                        'selling_price' => 699.99,
                        'storage_id' => $this->storage->id,
                        'imei' => 'IMEI_NOMFG_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_special_characters_in_model(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15 Pro Max 256GB',
                        'colour' => 'Black',
                        'grade' => 'A+',
                        'battery' => '95%',
                        'cost' => 800.00,
                        'selling_price' => 1199.99,
                        'storage_id' => $this->storage->id,
                        'imei' => 'SPECIAL_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_item_with_high_battery_percentage(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '100%',
                        'cost' => 500.00,
                        'selling_price' => 699.99,
                        'storage_id' => $this->storage->id,
                        'imei' => 'IMEI_BATT_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_multiple_items_in_single_request(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/inventory/items/update', [
                'items' => [
                    [
                        'manufacturer' => 'Apple',
                        'model' => 'iPhone 15',
                        'colour' => 'Black',
                        'grade' => 'A',
                        'battery' => '90%',
                        'cost' => 500.00,
                        'selling_price' => 699.99,
                        'storage_id' => $this->storage->id,
                        'imei' => 'MULTI_1_'.time(),
                        'type' => 'device',
                    ],
                    [
                        'manufacturer' => 'Samsung',
                        'model' => 'Galaxy S24',
                        'colour' => 'White',
                        'grade' => 'B',
                        'battery' => '85%',
                        'cost' => 400.00,
                        'selling_price' => 549.99,
                        'storage_id' => $this->storage->id,
                        'imei' => 'MULTI_2_'.time(),
                        'type' => 'device',
                    ],
                ],
            ]);

        $response->assertStatus(200);
    }
}
