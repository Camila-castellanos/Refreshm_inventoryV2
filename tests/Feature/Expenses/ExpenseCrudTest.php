<?php

namespace Tests\Feature\Expenses;

use App\Models\CashOnHand;
use App\Models\Expense;
use Tests\TestCaseWithCompany;

class ExpenseCrudTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();

        CashOnHand::factory()->create([
            'user_id' => $this->owner->id,
            'balance' => 1000.00,
        ]);
    }

    public function test_show_returns_expenses_page(): void
    {
        Expense::factory()->forOwner($this->owner)->create();
        Expense::factory()->forOwner($this->owner)->create(['name' => 'Office Supplies']);

        $response = $this->actingAs($this->owner)
            ->get('/accounting/expenses');

        $response->assertStatus(200);
    }

    public function test_create_returns_create_page(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/expenses/create');

        $response->assertStatus(200);
    }

    public function test_store_creates_expense(): void
    {
        $data = [
            'items' => [
                [
                    'date' => now()->format('Y-m-d'),
                    'name' => 'Office Supplies',
                    'category' => 'Supplies',
                    'amount' => 100.00,
                    'tax' => 0,
                    'tax_id' => null,
                    'subtotal' => 100.00,
                    'total' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/expenses', $data);

        $response->assertStatus(201);

        $this->assertDatabaseHas('expenses', [
            'name' => 'Office Supplies',
            'category' => 'Supplies',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/expenses', []);

        $response->assertSessionHasErrors();
    }

    public function test_store_updates_cash_on_hand_balance(): void
    {
        $data = [
            'items' => [
                [
                    'date' => now()->format('Y-m-d'),
                    'name' => 'Office Supplies',
                    'category' => 'Supplies',
                    'amount' => 100.00,
                    'tax' => 0,
                    'tax_id' => null,
                    'subtotal' => 100.00,
                    'total' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/expenses', $data);

        $response->assertStatus(201);

        $cashOnHand = CashOnHand::where('user_id', $this->owner->id)->first();
        $this->assertEquals(900.00, $cashOnHand->balance);
    }

    public function test_update_modifies_expense(): void
    {
        $expense = Expense::factory()->forOwner($this->owner)->create([
            'name' => 'Original Expense',
            'total' => 50.00,
        ]);

        $data = [
            'items' => [
                [
                    'id' => $expense->id,
                    'date' => now()->format('Y-m-d'),
                    'name' => 'Updated Expense',
                    'category' => 'Utilities',
                    'amount' => 75.00,
                    'tax' => 0,
                    'tax_id' => null,
                    'subtotal' => 75.00,
                    'total' => 75.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/expenses/update', $data);

        $response->assertStatus(200);

        $expense->refresh();
        $this->assertEquals('Updated Expense', $expense->name);
    }

    public function test_obliterate_deletes_multiple_expenses(): void
    {
        $expense1 = Expense::factory()->forOwner($this->owner)->create();
        $expense2 = Expense::factory()->forOwner($this->owner)->create();

        $data = [
            ['id' => $expense1->id],
            ['id' => $expense2->id],
        ];

        $response = $this->actingAs($this->owner)
            ->delete('/expenses/obliterate', $data);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('expenses', ['id' => $expense1->id]);
        $this->assertDatabaseMissing('expenses', ['id' => $expense2->id]);
    }

    public function test_store_creates_multiple_expenses(): void
    {
        $data = [
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
                    'amount' => 100.00,
                    'tax' => 0,
                    'tax_id' => null,
                    'subtotal' => 100.00,
                    'total' => 100.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post('/expenses', $data);

        $response->assertStatus(201);

        $this->assertDatabaseCount('expenses', 2);
    }

    public function test_expenses_belong_to_user(): void
    {
        $expense = Expense::factory()->forOwner($this->owner)->create();

        $this->assertEquals($this->owner->id, $expense->user_id);
    }

    public function test_expenses_have_correct_attributes(): void
    {
        $expense = Expense::factory()->forOwner($this->owner->id)->create([
            'name' => 'Test Expense',
            'category' => 'Maintenance',
            'amount' => 200.00,
            'tax' => 10.00,
            'total' => 210.00,
        ]);

        $expense->refresh();

        $this->assertEquals('Test Expense', $expense->name);
        $this->assertEquals('Maintenance', $expense->category);
        $this->assertEquals(200.00, $expense->amount);
    }
}
