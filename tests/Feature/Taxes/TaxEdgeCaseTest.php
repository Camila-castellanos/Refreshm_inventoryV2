<?php

namespace Tests\Feature\Taxes;

use App\Models\Bill;
use App\Models\Sale;
use App\Models\Tax;
use Tests\TestCaseWithCompany;

class TaxEdgeCaseTest extends TestCaseWithCompany
{
    public function test_create_tax_with_negative_percentage_allows(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => 'Negative Tax',
                'percentage' => -5,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_tax_with_percentage_over_100_allows(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => 'High Tax',
                'percentage' => 150,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_tax_with_zero_percentage_allows(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => 'Zero Tax',
                'percentage' => 0,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_tax_without_name_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'percentage' => 15,
            ]);

        $response->assertStatus(500);
    }

    public function test_create_tax_with_empty_name_fails(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => '',
                'percentage' => 15,
            ]);

        $response->assertStatus(500);
    }

    public function test_create_tax_with_name_too_long_allows(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => $longName,
                'percentage' => 15,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_tax_with_duplicate_name_allows(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Duplicate Tax',
            'percentage' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => 'Duplicate Tax',
                'percentage' => 15,
            ]);

        $response->assertStatus(200);
    }

    public function test_create_multiple_taxes_in_batch(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'taxes' => [
                    ['name' => 'Batch Tax 1', 'percentage' => 10],
                    ['name' => 'Batch Tax 2', 'percentage' => 15],
                    ['name' => 'Batch Tax 3', 'percentage' => 20],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('taxes', ['name' => 'Batch Tax 1', 'percentage' => 10]);
        $this->assertDatabaseHas('taxes', ['name' => 'Batch Tax 2', 'percentage' => 15]);
        $this->assertDatabaseHas('taxes', ['name' => 'Batch Tax 3', 'percentage' => 20]);
    }

    public function test_update_tax_with_sales_associated_updates_sales(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Original Tax',
            'percentage' => 10,
        ]);

        $sale = Sale::factory()->forOwner($this->owner)->create([
            'tax_id' => $tax->id,
            'subtotal' => 100,
            'flatTax' => 10,
            'tax' => 10,
            'total' => 110,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/update', [
                'taxes' => [
                    ['id' => $tax->id, 'name' => 'Updated Tax', 'percentage' => 20],
                ],
            ]);

        $response->assertStatus(200);

        $sale->refresh();
        $this->assertEquals(20, $sale->tax);
        $this->assertEquals(20, $sale->flatTax);
        $this->assertEquals(120, $sale->total);
    }

    public function test_update_tax_with_bills_associated_updates_bills(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Original Tax',
            'percentage' => 10,
        ]);

        $bill = Bill::factory()->forOwner($this->owner)->create([
            'tax_id' => $tax->id,
            'subtotal' => 100,
            'flat_tax' => 10,
            'tax' => 10,
            'total' => 110,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/update', [
                'taxes' => [
                    ['id' => $tax->id, 'name' => 'Updated Tax', 'percentage' => 20],
                ],
            ]);

        $response->assertStatus(200);

        $bill->refresh();
        $this->assertEquals(20, $bill->tax);
        $this->assertEquals(20, $bill->flat_tax);
        $this->assertEquals(120, $bill->total);
    }

    public function test_remove_tax_with_sales_associated_sets_tax_id_null(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Tax to Remove',
            'percentage' => 10,
        ]);

        $sale = Sale::factory()->forOwner($this->owner)->create([
            'tax_id' => $tax->id,
            'subtotal' => 100,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/remove', [
                'taxes' => [
                    ['id' => $tax->id],
                ],
            ]);

        $response->assertStatus(201);

        $sale->refresh();
        $this->assertNull($sale->tax_id);
    }

    public function test_remove_tax_with_bills_associated_sets_tax_id_null(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Tax to Remove',
            'percentage' => 10,
        ]);

        $bill = Bill::factory()->forOwner($this->owner)->create([
            'tax_id' => $tax->id,
            'subtotal' => 100,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/remove', [
                'taxes' => [
                    ['id' => $tax->id],
                ],
            ]);

        $response->assertStatus(201);

        $bill->refresh();
        $this->assertNull($bill->tax_id);
    }

    public function test_remove_nonexistent_tax_handles_error(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/remove', [
                'taxes' => [
                    ['id' => 99999],
                ],
            ]);

        $response->assertStatus(200);
    }

    public function test_list_taxes_with_empty_search_returns_all(): void
    {
        Tax::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/accounting/taxes/list?search=');

        $response->assertStatus(200);
        $response->assertJsonCount(4);
    }

    public function test_list_taxes_with_special_characters(): void
    {
        Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Tax @#$%',
            'percentage' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/taxes/list?search=@#$%');

        $response->assertStatus(200);
    }

    public function test_datewise_with_invalid_date_format(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/datewise', [
                'start' => 'invalid-date',
                'end' => 'also-invalid',
            ]);

        $response->assertStatus(400);
    }

    public function test_datewise_with_start_greater_than_end(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/datewise', [
                'start' => '2024-12-31',
                'end' => '2024-01-01',
            ]);

        $response->assertStatus(200);
    }

    public function test_create_tax_with_max_length_name_succeeds(): void
    {
        $maxLengthName = str_repeat('A', 255);

        $response = $this->actingAs($this->owner)
            ->post('/taxes', [
                'name' => $maxLengthName,
                'percentage' => 15,
            ]);

        $response->assertStatus(200);
    }

    public function test_update_tax_name_only_without_changing_percentage(): void
    {
        $tax = Tax::factory()->forOwner($this->owner)->create([
            'name' => 'Original Name',
            'percentage' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/accounting/taxes/update', [
                'taxes' => [
                    ['id' => $tax->id, 'name' => 'New Name', 'percentage' => 10],
                ],
            ]);

        $response->assertStatus(200);

        $tax->refresh();
        $this->assertEquals('New Name', $tax->name);
    }
}
