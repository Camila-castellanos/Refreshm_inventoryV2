<?php

namespace Tests;

use App\Models\Company;
use App\Models\Item;
use App\Models\Shop;
use App\Models\Storage;
use App\Models\Tab;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

abstract class TestCaseWithCompany extends TestCase
{
    use RefreshDatabase;

    protected ?Company $company = null;

    protected ?User $owner = null;

    protected ?User $user = null;

    protected ?Shop $shop = null;

    protected ?Storage $storage = null;

    protected ?Vendor $vendor = null;

    protected ?Tab $tab = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCompanyStructure();
    }

    protected function createCompanyStructure(): void
    {
        $this->company = Company::factory()->create();

        $this->owner = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'OWNER',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
        ]);

        $this->shop = Shop::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->storage = Storage::factory()->create([
            'company_id' => $this->company->id,
            'limit' => 100,
            'is_default' => true,
        ]);

        $this->vendor = Vendor::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->tab = Tab::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->company->owner_id = $this->owner->id;
        $this->company->save();
    }

    protected function loginAsOwner(): self
    {
        Auth::login($this->owner);

        return $this;
    }

    protected function loginAsUser(): self
    {
        Auth::login($this->user);

        return $this;
    }

    protected function loginAs(?User $user = null): self
    {
        Auth::login($user ?? $this->owner);

        return $this;
    }

    protected function createItem(array $overrides = []): Item
    {
        return Item::factory()->create(array_merge([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
        ], $overrides));
    }

    protected function createItems(int $count, array $overrides = []): \Illuminate\Database\Eloquent\Collection
    {
        return Item::factory()->count($count)->create(array_merge([
            'user_id' => $this->owner->id,
            'shop_id' => $this->shop->id,
            'storage_id' => $this->storage->id,
            'vendor_id' => $this->vendor->id,
        ], $overrides));
    }

    protected function createStorage(array $overrides = []): Storage
    {
        return Storage::factory()->create(array_merge([
            'company_id' => $this->company->id,
        ], $overrides));
    }

    protected function createShop(array $overrides = []): Shop
    {
        return Shop::factory()->create(array_merge([
            'company_id' => $this->company->id,
        ], $overrides));
    }

    protected function createVendor(array $overrides = []): Vendor
    {
        return Vendor::factory()->create(array_merge([
            'user_id' => $this->owner->id,
        ], $overrides));
    }

    protected function createTab(array $overrides = []): Tab
    {
        return Tab::factory()->create(array_merge([
            'user_id' => $this->owner->id,
        ], $overrides));
    }

    protected function withoutCompanyScope(): self
    {
        Item::withoutGlobalScope(\App\Models\Scopes\CompanyItemScope::class);

        return $this;
    }

    protected function withoutGlobalScopes(): self
    {
        Item::withoutGlobalScopes();

        return $this;
    }
}
