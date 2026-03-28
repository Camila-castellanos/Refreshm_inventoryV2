<?php

namespace Tests\Feature\Integration;

use Tests\TestCaseWithCompany;

class PaymentCascadeTest extends TestCaseWithCompany
{
    public function test_can_access_payment_list(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments');

        $response->assertStatus(200);
    }
}
