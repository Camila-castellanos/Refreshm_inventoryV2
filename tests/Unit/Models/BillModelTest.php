<?php

namespace Tests\Unit\Models;

use App\Models\Bill;
use Tests\TestCaseWithCompany;

class BillModelTest extends TestCaseWithCompany
{
    public function test_bill_casts_date_as_datetime(): void
    {
        $bill = Bill::factory()->create([
            'user_id' => $this->owner->id,
            'date' => '2024-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $bill->date);
    }

    public function test_bill_casts_date_formats_correctly(): void
    {
        $bill = Bill::factory()->create([
            'user_id' => $this->owner->id,
            'date' => '2024-01-15 10:30:00',
        ]);

        $serialized = $bill->toArray()['date'];

        $this->assertStringContainsString('2024-01-15', $serialized);
    }

    public function test_bill_has_default_status(): void
    {
        $bill = Bill::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertNotNull($bill->status);
    }
}
