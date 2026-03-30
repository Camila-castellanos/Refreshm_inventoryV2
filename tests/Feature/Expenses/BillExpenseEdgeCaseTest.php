<?php

namespace Tests\Feature\Expenses;

use Tests\TestCaseWithCompany;

class BillExpenseEdgeCaseTest extends TestCaseWithCompany
{
    public function test_expense_store_with_items(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', [
                'items' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'name' => 'Office Supplies',
                        'category' => 'Supplies',
                        'amount' => 50.00,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => 50.00,
                        'total' => 50.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_expense_with_zero_amount_in_items(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', [
                'items' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'name' => 'Free Item',
                        'category' => 'Supplies',
                        'amount' => 0,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => 0,
                        'total' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_expense_with_negative_amount(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', [
                'items' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'name' => 'Refund',
                        'category' => 'Supplies',
                        'amount' => -50.00,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => -50.00,
                        'total' => -50.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_expense_future_date(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', [
                'items' => [
                    [
                        'date' => now()->addDays(30)->format('Y-m-d'),
                        'name' => 'Future Expense',
                        'category' => 'Supplies',
                        'amount' => 50.00,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => 50.00,
                        'total' => 50.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_expense_without_category_fails_validation(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', [
                'items' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'name' => 'No Category',
                        'amount' => 50.00,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => 50.00,
                        'total' => 50.00,
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('items.0.category');
    }

    public function test_bill_store(): void
    {
        $vendor = $this->createVendor();

        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'Test Vendor',
                        'vendor_id' => $vendor->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-001',
                        'subtotal' => 100.00,
                        'tax' => 13.00,
                        'total' => 113.00,
                        'status' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_bill_with_zero_amount(): void
    {
        $vendor = $this->createVendor();

        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'Test Vendor',
                        'vendor_id' => $vendor->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-002',
                        'subtotal' => 0,
                        'tax' => 0,
                        'total' => 0,
                        'status' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_bill_without_vendor(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'No Vendor Bill',
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-003',
                        'subtotal' => 100.00,
                        'tax' => 13.00,
                        'total' => 113.00,
                        'status' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_bill_with_long_notes(): void
    {
        $vendor = $this->createVendor();
        $longNotes = str_repeat('Lorem ipsum dolor sit amet. ', 50);

        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'Test Vendor',
                        'vendor_id' => $vendor->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-004',
                        'subtotal' => 100.00,
                        'tax' => 13.00,
                        'total' => 113.00,
                        'status' => 0,
                        'notes' => $longNotes,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_multiple_expenses_in_array(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', [
                'items' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'name' => 'Expense 1',
                        'category' => 'Supplies',
                        'amount' => 50.00,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => 50.00,
                        'total' => 50.00,
                    ],
                    [
                        'date' => now()->format('Y-m-d'),
                        'name' => 'Expense 2',
                        'category' => 'Utilities',
                        'amount' => 75.00,
                        'tax' => 0,
                        'tax_id' => null,
                        'subtotal' => 75.00,
                        'total' => 75.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    public function test_multiple_bills_in_array(): void
    {
        $vendor1 = $this->createVendor();
        $vendor2 = $this->createVendor();

        $response = $this->actingAs($this->owner)
            ->post('/bills', [
                'items' => [
                    [
                        'vendor' => 'Vendor 1',
                        'vendor_id' => $vendor1->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-005',
                        'subtotal' => 100.00,
                        'tax' => 0,
                        'total' => 100.00,
                        'status' => 0,
                    ],
                    [
                        'vendor' => 'Vendor 2',
                        'vendor_id' => $vendor2->id,
                        'date' => now()->format('Y-m-d'),
                        'invoice' => 'INV-006',
                        'subtotal' => 200.00,
                        'tax' => 0,
                        'total' => 200.00,
                        'status' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }
}
