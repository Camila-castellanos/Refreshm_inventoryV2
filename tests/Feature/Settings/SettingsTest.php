<?php

namespace Tests\Feature\Settings;

use Tests\TestCaseWithCompany;

class SettingsTest extends TestCaseWithCompany
{
    public function test_can_get_printable_tag_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/user/printable-tag-fields');

        $response->assertStatus(200);
    }

    public function test_can_update_printable_tag_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/printable-tag-fields', [
                'fields' => ['manufacturer', 'model', 'storage', 'imei'],
            ]);

        $response->assertStatus(200);

        $this->owner->refresh();
        $this->assertContains('manufacturer', $this->owner->printable_tag_fields);
    }

    public function test_can_get_printable_invoice_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/user/printable-invoice-fields');

        $response->assertStatus(200);
    }

    public function test_can_update_printable_invoice_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->put('/user/printable-invoice-fields', [
                'fields' => ['logo', 'header', 'subtotal', 'total'],
            ]);

        $response->assertStatus(200);

        $this->owner->refresh();
        $this->assertContains('logo', $this->owner->printable_invoice_fields);
    }

    public function test_settings_require_authentication(): void
    {
        $response = $this->get('/user/printable-tag-fields');

        $response->assertRedirect('/login');
    }
}
