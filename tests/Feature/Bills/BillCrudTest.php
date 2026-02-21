<?php

namespace Tests\Feature\Bills;

use App\Models\Bill;
use App\Models\Vendor;
use Tests\TestCaseWithCompany;

class BillCrudTest extends TestCaseWithCompany
{
    public function test_can_view_bills_list(): void
    {
        Bill::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/accounting/bills');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('items'));
    }

    public function test_can_view_bills_list_filtered_by_status(): void
    {
        Bill::factory()->forOwner($this->owner)->create(['status' => 1]);
        Bill::factory()->forOwner($this->owner)->create(['status' => 0]);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/bills?status=paid');

        $response->assertStatus(200);
    }

    public function test_can_view_bill_create_form(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/bills/create');

        $response->assertStatus(200);
    }

    public function test_can_create_bill(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'Test Vendor',
                        'vendor_id' => $vendor->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-001',
                        'subtotal' => 100.00,
                        'tax' => 0,
                        'total' => 100.00,
                        'status' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bills', [
            'vendor' => 'Test Vendor',
            'invoice' => 'INV-001',
        ]);
    }

    public function test_can_create_bill_with_tax(): void
    {
        $vendor = Vendor::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'Test Vendor Tax',
                        'vendor_id' => $vendor->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-002',
                        'subtotal' => 100.00,
                        'tax' => 13,
                        'total' => 113.00,
                        'status' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bills', [
            'vendor' => 'Test Vendor Tax',
            'tax' => 13,
        ]);
    }

    public function test_can_delete_bill(): void
    {
        $bill = Bill::factory()->forOwner($this->owner)->create();

        $response = $this->actingAs($this->owner)
            ->delete('/bills/'.$bill->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('bills', ['id' => $bill->id]);
    }

    public function test_bills_require_authentication(): void
    {
        $response = $this->get('/accounting/bills');

        $response->assertRedirect('/login');
    }

    public function test_create_bill_requires_authentication(): void
    {
        $response = $this->post('/bills', [
            'items' => [
                ['vendor' => 'Test', 'total' => 100],
            ],
        ]);

        $response->assertRedirect('/login');
    }
}
