<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_password_returns_true_when_set(): void
    {
        $customer = Customer::factory()->create(['password' => Hash::make('test-password')]);

        $this->assertTrue($customer->hasPassword());
    }

    public function test_has_password_returns_false_when_null(): void
    {
        $customer = Customer::factory()->create(['password' => null]);

        $this->assertFalse($customer->hasPassword());
    }

    public function test_has_password_returns_false_when_empty_string(): void
    {
        $customer = Customer::factory()->create(['password' => '']);

        $this->assertFalse($customer->hasPassword());
    }

    public function test_customer_fillable_includes_preferences(): void
    {
        $customer = new Customer();
        $fillable = $customer->getFillable();
        $this->assertContains('default_store', $fillable);
        $this->assertContains('default_shipping', $fillable);
    }
}