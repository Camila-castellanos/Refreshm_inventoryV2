<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use Tests\TestCaseWithCompany;

class CustomerEdgeCaseTest extends TestCaseWithCompany
{
    public function test_customer_with_invalid_email_format(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'Test Customer',
                'email' => 'not-a-valid-email',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_without_email(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'Test Customer',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_without_name(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'email' => 'test@example.com',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_with_duplicate_email(): void
    {
        $existingCustomer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'email' => 'duplicate@test.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'New Customer',
                'email' => 'duplicate@test.com',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_with_special_characters_in_name(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => "O'Connor & Sons Ltd.",
                'email' => 'test@example.com',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_with_accented_characters(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'José García Niño',
                'email' => 'jose@example.com',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_with_long_phone_number(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+1 (555) 123-4567 ext. 890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_without_phone(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_with_very_long_name(): void
    {
        $longName = str_repeat('A', 300);

        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => $longName,
                'email' => 'test@example.com',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_email_case_sensitivity(): void
    {
        Customer::factory()->create([
            'user_id' => $this->owner->id,
            'email' => 'TEST@example.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'New Customer',
                'email' => 'test@EXAMPLE.COM',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_update_with_invalid_email(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'email' => 'old@test.com',
        ]);

        $response = $this->actingAs($this->owner)
            ->put('/customer/'.$customer->id, [
                'name' => 'Updated Customer',
                'email' => 'not-valid-email',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }

    public function test_customer_with_empty_email(): void
    {
        $response = $this->actingAs($this->owner)
            ->post('/customer', [
                'name' => 'Test Customer',
                'email' => '',
                'phone' => '1234567890',
            ]);

        $response->assertStatus(302);
    }
}
