<?php

namespace Tests\Feature\CustomFields;

use App\Models\CustomField;
use Tests\TestCaseWithCompany;

class CustomFieldCrudTest extends TestCaseWithCompany
{
    public function test_store_creates_custom_field(): void
    {
        $data = [
            'text' => 'Color',
            'type' => 'select',
            'value' => 'Red,Blue,Green',
            'active' => true,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/customfields', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('custom_fields', [
            'text' => 'Color',
            'type' => 'select',
        ]);
    }

    public function test_store_validates_required_text(): void
    {
        $data = [
            'type' => 'text',
            'value' => 'test',
            'active' => true,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/customfields', $data);

        $response->assertStatus(500);
    }

    public function test_store_validates_required_type(): void
    {
        $data = [
            'text' => 'Test Field',
            'value' => 'test',
            'active' => true,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/customfields', $data);

        $response->assertStatus(500);
    }

    public function test_update_modifies_custom_field(): void
    {
        $customField = CustomField::factory()->forOwner($this->owner->id)->create([
            'text' => 'Original Text',
            'type' => 'text',
        ]);

        $data = [
            'text' => 'Updated Text',
            'type' => 'number',
        ];

        $response = $this->actingAs($this->owner)
            ->put("/customfields/{$customField->id}", $data);

        $response->assertStatus(200);

        $customField->refresh();
        $this->assertEquals('Updated Text', $customField->text);
        $this->assertEquals('number', $customField->type);
    }

    public function test_update_active_toggles_status(): void
    {
        $customField = CustomField::factory()->forOwner($this->owner->id)->create([
            'active' => 1,
        ]);

        $data = ['active' => false];

        $response = $this->actingAs($this->owner)
            ->post("/customfields/{$customField->id}/active", $data);

        $response->assertStatus(200);

        $customField->refresh();
        $this->assertEquals(0, $customField->active);
    }

    public function test_update_active_can_activate(): void
    {
        $customField = CustomField::factory()->forOwner($this->owner->id)->create([
            'active' => 0,
        ]);

        $data = ['active' => true];

        $response = $this->actingAs($this->owner)
            ->post("/customfields/{$customField->id}/active", $data);

        $response->assertStatus(200);

        $customField->refresh();
        $this->assertEquals(1, $customField->active);
    }

    public function test_destroy_deletes_custom_field(): void
    {
        $customField = CustomField::factory()->forOwner($this->owner->id)->create();

        $response = $this->actingAs($this->owner)
            ->delete("/customfields/{$customField->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('custom_fields', ['id' => $customField->id]);
    }

    public function test_custom_field_belongs_to_user(): void
    {
        $customField = CustomField::factory()->forOwner($this->owner)->create();

        $this->assertEquals($this->owner->id, $customField->user_id);
    }

    public function test_store_creates_inactive_field(): void
    {
        $data = [
            'text' => 'Inactive Field',
            'type' => 'text',
            'value' => 'test',
            'active' => false,
        ];

        $response = $this->actingAs($this->owner)
            ->post('/customfields', $data);

        $response->assertStatus(200);

        $customField = CustomField::where('text', 'Inactive Field')->first();
        $this->assertEquals(0, $customField->active);
    }
}
