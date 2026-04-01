<?php

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\Sale;
use Tests\TestCaseWithCompany;

class SaleModelTest extends TestCaseWithCompany
{
    public function test_sale_has_many_items(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
        ]);

        $this->createItem(['sale_id' => $sale->id]);
        $this->createItem(['sale_id' => $sale->id]);
        $this->createItem(['sale_id' => $sale->id]);

        $this->assertCount(3, $sale->items);
    }

    public function test_sale_has_many_payments(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
        ]);

        Payment::factory()->create(['sale_id' => $sale->id, 'amount_paid' => 100]);
        Payment::factory()->create(['sale_id' => $sale->id, 'amount_paid' => 50]);

        $this->assertCount(2, $sale->payments);
    }

    public function test_sale_casts_date_as_datetime(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
            'date' => '2024-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $sale->date);
    }

    public function test_sale_casts_extra_as_array(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
            'extra' => ['key' => 'value', 'number' => 42],
        ]);

        $this->assertIsArray($sale->extra);
        $this->assertEquals('value', $sale->extra['key']);
    }

    public function test_serialize_date_formats_correctly(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
            'date' => '2024-01-15 10:30:00',
        ]);

        $serialized = $sale->toArray()['date'];

        $this->assertStringContainsString('2024-01-15', $serialized);
    }

    public function test_sale_without_global_scope_includes_all_channels(): void
    {
        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'system',
        ]);
        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'channel' => 'ecommerce',
        ]);

        $sales = Sale::withoutGlobalScopes()->get();

        $this->assertGreaterThanOrEqual(2, $sales->count());
    }
}
