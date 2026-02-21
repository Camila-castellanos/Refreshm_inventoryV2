<?php

namespace Tests\Feature\Taxes;

use App\Models\Tax;
use Tests\TestCaseWithCompany;

class TaxCrudTest extends TestCaseWithCompany
{
    public function test_can_view_taxes_report(): void
    {
        Tax::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/accounting/taxes');

        $response->assertStatus(200);
    }

    public function test_can_view_taxes_list(): void
    {
        Tax::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/accounting/taxes/list');

        $response->assertStatus(200);
    }

    public function test_can_create_tax(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => 'Test Tax',
                'percentage' => 15,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('taxes', [
            'name' => 'Test Tax',
            'percentage' => 15,
        ]);
    }

    public function test_can_view_tax(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->get('/taxes/'.$tax->id);

        $response->assertStatus(200);
    }

    public function test_can_update_multiple_taxes(): void
    {
        $tax1 = Tax::factory()->forOwner($this->owner)->create(['percentage' => 10]);
        $tax2 = Tax::factory()->forOwner($this->owner)->create(['percentage' => 15]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/update', [
                'taxes' => [
                    ['id' => $tax1->id, 'name' => $tax1->name, 'percentage' => 12],
                    ['id' => $tax2->id, 'name' => $tax2->name, 'percentage' => 18],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_can_remove_multiple_taxes(): void
    {
        $tax1 = Tax::factory()->forOwner($this->owner)->create();
        $tax2 = Tax::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/remove', [
                'taxes' => [
                    ['id' => $tax1->id],
                    ['id' => $tax2->id],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('taxes', ['id' => $tax1->id]);
        $this->assertDatabaseMissing('taxes', ['id' => $tax2->id]);
    }

    public function test_taxes_require_authentication(): void
    {
        $response = $this->get('/accounting/taxes');

        $response->assertRedirect('/login');
    }
}
