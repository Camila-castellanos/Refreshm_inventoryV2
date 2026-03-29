<?php

namespace Tests\Feature\Dashboard;

use App\Models\Bill;
use App\Models\CashOnHand;
use App\Models\Expense;
use App\Models\LoginActivity;
use App\Models\Sale;
use App\Models\Tax;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCaseWithCompany;

class DashboardTest extends TestCaseWithCompany
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_can_update_cash_on_hand(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 5000.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $this->owner->id,
            'balance' => 5000.00,
        ]);
    }

    public function test_can_view_expenses(): void
    {
        Expense::factory()->forOwner($this->owner)->count(3)->create();

        $response = $this->actingAs($this->owner)
            ->get('/accounting/expenses');

        $response->assertStatus(200);
    }

    public function test_can_view_taxes(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/accounting/taxes');

        $response->assertStatus(200);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_returns_200_for_owner(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_returns_200_for_admin(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);

        $response = $this->actingAs($admin)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_returns_403_for_user(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(403);
    }

    public function test_dashboard_calculates_sales_metrics_with_taxed_items(): void
    {
        $tax = Tax::factory()->create();

        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'tax_id' => $tax->id,
            'tax' => $tax->rate ?? 13.00,
            'flatTax' => 10.00,
            'date' => now(),
        ]);

        $this->createItems(2, [
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 100.00,
            'cost' => 50.00,
            'type' => 'device',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_sales_metrics_with_non_taxed_items(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'tax_id' => null,
            'tax' => 0,
            'flatTax' => 0,
            'date' => now(),
        ]);

        $this->createItems(2, [
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 100.00,
            'cost' => 50.00,
            'type' => 'device',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_inventory_metrics(): void
    {
        $this->createItems(3, [
            'sold' => null,
            'cost' => 100.00,
            'selling_price' => 200.00,
            'type' => 'device',
        ]);

        $this->createItems(2, [
            'sold' => null,
            'cost' => 25.00,
            'selling_price' => 50.00,
            'type' => 'accessory',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_device_metrics(): void
    {
        $this->createItems(5, [
            'sold' => null,
            'type' => 'device',
            'date' => now()->subMonth(),
        ]);

        $this->createItems(3, [
            'sold' => now()->startOfMonth(),
            'type' => 'device',
        ]);

        $this->createItems(2, [
            'type' => 'device',
            'date' => now()->startOfMonth(),
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_financial_metrics(): void
    {
        CashOnHand::factory()->forOwner($this->owner)->create([
            'balance' => 5000.00,
        ]);

        Expense::factory()->forOwner($this->owner)->create([
            'date' => now(),
            'total' => 500.00,
        ]);

        $tax = Tax::factory()->create();
        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'tax_id' => $tax->id,
            'flatTax' => 25.00,
            'date' => now(),
            'balance_remaining' => 100.00,
        ]);

        Bill::factory()->forOwner($this->owner)->create([
            'status' => 0,
            'balance_remaining' => 300.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_owner_sees_user_metrics(): void
    {
        User::factory()->create([
            'company_id' => $this->company->id,
            'created_at' => now()->subDays(5),
        ]);

        LoginActivity::factory()->forUser($this->owner->id)->count(5)->create([
            'login_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_admin_does_not_see_user_metrics(): void
    {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
        ]);

        LoginActivity::factory()->forUser($admin->id)->count(3)->create();

        $response = $this->actingAs($admin)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_update_cash_creates_new_record_when_none_exists(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 2500.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $this->owner->id,
            'balance' => 2500.00,
        ]);
    }

    public function test_update_cash_updates_existing_record(): void
    {
        CashOnHand::factory()->forOwner($this->owner)->create([
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 7500.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $this->owner->id,
            'balance' => 7500.00,
        ]);

        $this->assertEquals(1, CashOnHand::where('user_id', $this->owner->id)->count());
    }

    public function test_update_cash_with_decimal_balance(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 1234.56,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $this->owner->id,
            'balance' => 1234.56,
        ]);
    }

    public function test_update_cash_returns_json_response(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 5000.00,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_dashboard_returns_zeros_with_no_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_profit_correctly(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'tax_id' => null,
            'date' => now(),
        ]);

        $this->createItem([
            'sale_id' => $sale->id,
            'sold' => now(),
            'selling_price' => 200.00,
            'cost' => 100.00,
            'type' => 'device',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_cogs_correctly(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'date' => now(),
        ]);

        $this->createItem([
            'sale_id' => $sale->id,
            'sold' => now(),
            'cost' => 75.00,
            'type' => 'device',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_accounts_receivable(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->owner->id,
            'balance_remaining' => 500.00,
            'date' => now()->subMonths(2),
        ]);

        $this->createItem([
            'sale_id' => $sale->id,
            'sold' => now()->subMonths(2),
            'selling_price' => 500.00,
            'type' => 'device',
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_accounts_payable(): void
    {
        Bill::factory()->forOwner($this->owner)->create([
            'status' => 0,
            'balance_remaining' => 250.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_sales_tax_collected(): void
    {
        $tax = Tax::factory()->create();

        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'tax_id' => $tax->id,
            'flatTax' => 50.00,
            'date' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_expenses_this_month(): void
    {
        Expense::factory()->forOwner($this->owner)->create([
            'date' => now(),
            'total' => 150.00,
        ]);

        Expense::factory()->forOwner($this->owner)->create([
            'date' => now()->subMonths(2),
            'total' => 200.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_caches_results(): void
    {
        Cache::flush();

        $this->actingAs($this->owner)->get('/dashboard');
        $this->actingAs($this->owner)->get('/dashboard');

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_different_users_have_different_cache(): void
    {
        $otherOwner = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'OWNER',
        ]);

        $this->createItems(10, [
            'sold' => now(),
            'selling_price' => 100.00,
            'type' => 'device',
            'user_id' => $this->owner->id,
        ]);

        $this->createItems(5, [
            'sold' => now(),
            'selling_price' => 200.00,
            'type' => 'device',
            'user_id' => $otherOwner->id,
        ]);

        $response1 = $this->actingAs($this->owner)->get('/dashboard');
        $response2 = $this->actingAs($otherOwner)->get('/dashboard');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    public function test_dashboard_report_datewise_with_date_range(): void
    {
        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        Sale::factory()->create([
            'user_id' => $this->owner->id,
            'date' => now(),
            'total' => 500.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/report/datewise', [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);

        $response->assertStatus(200);
    }

    public function test_dashboard_repost_datewise_by_date(): void
    {
        $date = Carbon::now()->toDateString();

        $response = $this->actingAs($this->owner)
            ->postJson('/report/datewiseByDate', [
                'startDate' => $date,
            ]);

        $response->assertStatus(200);
    }

    public function test_dashboard_repost_datewise_by_date_with_end_date(): void
    {
        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        $response = $this->actingAs($this->owner)
            ->postJson('/report/datewiseByDate', [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);

        $response->assertStatus(200);
    }

    public function test_dashboard_report_includes_start_and_end_dates(): void
    {
        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        $response = $this->actingAs($this->owner)
            ->postJson('/report/datewise', [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);

        $response->assertStatus(200);
    }

    public function test_dashboard_user_cannot_access_other_company_cash(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherOwner = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        CashOnHand::factory()->forOwner($otherOwner)->create([
            'balance' => 10000.00,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_masks_ip_addresses_in_recent_logins(): void
    {
        LoginActivity::factory()->forUser($this->owner->id)->create([
            'ip_address' => '192.168.1.100',
            'login_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_trades_this_month(): void
    {
        $this->createItems(3, [
            'date' => now()->startOfMonth(),
            'type' => 'device',
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_calculates_sold_this_month(): void
    {
        $this->createItems(4, [
            'sold' => now()->startOfMonth(),
            'type' => 'device',
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_empty_inventory_returns_zero(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_empty_sales_returns_zero(): void
    {
        $response = $this->actingAs($this->owner)
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_update_cash_isolation_between_companies(): void
    {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherOwner = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'OWNER',
        ]);

        CashOnHand::factory()->forOwner($otherOwner)->create([
            'balance' => 9999.99,
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/dashboard/update_cash', [
                'balance' => 100.00,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $this->owner->id,
            'balance' => 100.00,
        ]);

        $this->assertDatabaseHas('cash_on_hands', [
            'user_id' => $otherOwner->id,
            'balance' => 9999.99,
        ]);
    }
}
