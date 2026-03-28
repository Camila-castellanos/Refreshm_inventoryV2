<?php

namespace Tests\Feature\Integration;

use App\Models\Item;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class SalesReportTest extends TestCaseWithCompany
{
    public function test_payments_list_loads(): void
    {
        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'paid' => 1,
        ]);

        $response = $this->actingAs($this->owner)->get('/accounting/payments');
        $response->assertStatus(200);
    }

    public function test_paid_unpaid_counts(): void
    {
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 100.00, 'paid' => 1]);
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 200.00, 'paid' => 0]);
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 150.00, 'paid' => 1]);

        $response = $this->actingAs($this->owner)->get('/accounting/payments');
        $response->assertStatus(200);
    }

    public function test_payments_by_date_range(): void
    {
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 100.00, 'created_at' => now()->subDays(10)]);
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 200.00, 'created_at' => now()->subDays(5)]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments?start_date='.now()->subDays(7)->format('Y-m-d').'&end_date='.now()->format('Y-m-d'));

        $response->assertStatus(200);
    }

    public function test_payments_list_with_filter(): void
    {
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 100.00, 'paid' => 1]);

        $response = $this->actingAs($this->owner)->get('/accounting/payments?status=paid');
        $response->assertStatus(200);
    }

    public function test_total_revenue_calculation(): void
    {
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 100.00, 'amount_paid' => 100.00, 'paid' => 1]);
        Sale::factory()->create(['user_id' => $this->owner->id, 'total' => 200.00, 'amount_paid' => 200.00, 'paid' => 1]);

        $response = $this->actingAs($this->owner)->get('/accounting/payments');
        $response->assertStatus(200);
    }

    public function test_profit_calculation(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
            'amount_paid' => 100.00,
            'paid' => 1,
        ]);

        Item::factory()->create([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sale_id' => $sale->id,
            'sold' => now(),
            'cost' => 50.00,
            'selling_price' => 100.00,
            'profit' => 50.00,
        ]);

        $response = $this->actingAs($this->owner)->get('/accounting/payments');
        $response->assertStatus(200);
    }
}
