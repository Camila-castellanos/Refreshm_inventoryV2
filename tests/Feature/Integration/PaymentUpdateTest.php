<?php

namespace Tests\Feature\Integration;

use App\Models\Sale;
use Tests\TestCaseWithCompany;

class PaymentUpdateTest extends TestCaseWithCompany
{
    public function test_can_view_payment_list(): void
    {
        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }
}
