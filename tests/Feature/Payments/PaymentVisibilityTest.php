<?php

namespace Tests\Feature\Payments;

use App\Models\Company;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Storage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCaseWithCompany;

class PaymentVisibilityTest extends TestCaseWithCompany
{
    use RefreshDatabase;

    protected function createSaleWithItem(User $user, $amount = 100)
    {
        $sale = Sale::factory()->create([
            'user_id' => $user->id,
            'total' => $amount,
            'amount_paid' => $amount,
            'paid' => 1,
            'created_at' => now(),
        ]);

        Item::factory()->create([
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'sold' => now(),
            'selling_price' => $amount,
            'customer' => 'Test Customer',
        ]);

        return $sale;
    }

    public function test_admin_can_view_all_payments_in_company(): void
    {
        // Create a Store for the admin user (foreign key constraint)
        $store = Store::factory()->create();

        // Create an ADMIN user in the same company
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
            'store_id' => $store->id,
        ]);

        // Create sales for different users
        $saleOwner = $this->createSaleWithItem($this->owner);
        $saleUser = $this->createSaleWithItem($this->user);
        $saleAdmin = $this->createSaleWithItem($admin);

        // Act as Admin
        $response = $this->actingAs($admin)
            ->get('/accounting/payments');

        $response->assertStatus(200);

        // Assert all sales are present
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/Payments')
            ->has('items', 3)
        );
    }

    public function test_owner_can_view_all_payments_in_company(): void
    {
        // Owner is created in TestCaseWithCompany setup

        // Create sales for different users
        $saleOwner = $this->createSaleWithItem($this->owner);
        $saleUser = $this->createSaleWithItem($this->user);

        // Act as Owner
        $response = $this->actingAs($this->owner)
            ->get('/accounting/payments');

        $response->assertStatus(200);

        // Assert all sales are present (Owner's + User's)
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/Payments')
            ->has('items', 2)
        );
    }

    public function test_user_cannot_access_payments_module(): void
    {
        // Act as regular User
        $response = $this->actingAs($this->user)
            ->get('/accounting/payments');

        // Regular users are restricted by middleware (role:ADMIN,OWNER)
        $response->assertStatus(302);
    }

    public function test_admin_cannot_view_payments_from_other_company(): void
    {
        // Create a Store for the admin user
        $store = Store::factory()->create();

        // Create an ADMIN in current company
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
            'store_id' => $store->id,
        ]);

        // Create another company and user
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'USER',
        ]);

        // Create storage/shop for other user to satisfy factory requirements
        $otherShop = Shop::factory()->create(['company_id' => $otherCompany->id]);
        $otherStorage = Storage::factory()->create(['company_id' => $otherCompany->id]);

        // Create sale for other company user manually
        $saleOther = Sale::factory()->create([
            'user_id' => $otherUser->id,
            'total' => 100,
            'created_at' => now(),
        ]);

        Item::factory()->create([
            'sale_id' => $saleOther->id,
            'user_id' => $otherUser->id,
            'shop_id' => $otherShop->id,
            'storage_id' => $otherStorage->id,
            'sold' => now(),
            'selling_price' => 100,
            'customer' => 'Other Customer',
        ]);

        // Act as Admin of first company
        $response = $this->actingAs($admin)
            ->get('/accounting/payments');

        $response->assertStatus(200);

        // Assert saleOther is NOT in items
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Accounting/Payments')
            ->where('items', fn ($items) => collect($items)->doesntContain('sale_id', $saleOther->id))
        );
    }
}
