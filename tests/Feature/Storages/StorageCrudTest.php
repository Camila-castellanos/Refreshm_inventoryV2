<?php

namespace Tests\Feature\Storages;

use App\Models\Storage;
use Tests\TestCaseWithCompany;

class StorageCrudTest extends TestCaseWithCompany
{
    public function test_can_view_storages_list(): void
    {
        Storage::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/storages');

        $response->assertStatus(200);
    }

    public function test_can_create_single_storage(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/storages', [
                'storages' => [
                    [
                        'name' => 'Test Storage',
                        'limit' => 100,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('storages', [
            'name' => 'Test Storage',
            'limit' => 100,
        ]);
    }

    public function test_can_create_multiple_storages(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/storages', [
                'storages' => [
                    ['name' => 'Storage 1', 'limit' => 50],
                    ['name' => 'Storage 2', 'limit' => 75],
                    ['name' => 'Storage 3', 'limit' => 100],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('storages', ['name' => 'Storage 1']);
        $this->assertDatabaseHas('storages', ['name' => 'Storage 2']);
        $this->assertDatabaseHas('storages', ['name' => 'Storage 3']);
    }

    public function test_can_update_storage(): void
    {
        $storage = Storage::factory()->forOwner($this->owner)->create([
            'name' => 'Old Name',
            'limit' => 50,
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/storages/'.$storage->id, [
                'name' => 'Updated Name',
                'limit' => 200,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('storages', [
            'id' => $storage->id,
            'name' => 'Updated Name',
            'limit' => 200,
        ]);
    }

    public function test_can_delete_single_storage(): void
    {
        $storage = Storage::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post('/storages/destroy', [
                'storages' => [$storage->id],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('storages', ['id' => $storage->id]);
    }

    public function test_can_delete_multiple_storages(): void
    {
        $storage1 = Storage::factory()->forOwner($this->owner)->create();
        $storage2 = Storage::factory()->forOwner($this->owner)->create();
        $storage3 = Storage::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post('/storages/destroy', [
                'storages' => [$storage1->id, $storage2->id],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('storages', ['id' => $storage1->id]);
        $this->assertDatabaseMissing('storages', ['id' => $storage2->id]);
        $this->assertDatabaseHas('storages', ['id' => $storage3->id]);
    }

    public function test_can_reorder_storages(): void
    {
        $storage1 = Storage::factory()->forOwner($this->owner)->create(['priority' => 1]);
        $storage2 = Storage::factory()->forOwner($this->owner)->create(['priority' => 2]);
        $storage3 = Storage::factory()->forOwner($this->owner)->create(['priority' => 3]);

        $response = $this->actingAs($this->owner)
            ->post('/storages/reorder', [
                'order' => [$storage3->id, $storage2->id, $storage1->id],
            ]);

        $response->assertStatus(200);
    }

    public function test_storage_requires_name(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/storages', [
                'storages' => [
                    ['name' => '', 'limit' => 100],
                ],
            ]);

        $response->assertStatus(302);
    }

    public function test_storage_requires_limit(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/storages', [
                'storages' => [
                    ['name' => 'Test Storage'],
                ],
            ]);

        $response->assertStatus(302);
    }

    public function test_storage_limit_must_be_positive(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/storages', [
                'storages' => [
                    ['name' => 'Test Storage', 'limit' => 0],
                ],
            ]);

        $response->assertStatus(302);
    }
}
