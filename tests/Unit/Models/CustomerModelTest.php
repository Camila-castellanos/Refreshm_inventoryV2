<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use Tests\TestCaseWithCompany;

class CustomerModelTest extends TestCaseWithCompany
{
    public function test_get_phone_attribute_returns_string_value(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'phone' => '+1234567890',
        ]);

        $this->assertEquals('+1234567890', $customer->phone);
    }

    public function test_get_phone_attribute_returns_first_element_of_json_string(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'phone' => '["+1234567890","+0987654321"]',
        ]);

        $this->assertEquals('+1234567890', $customer->phone);
    }

    public function test_get_phone_attribute_returns_empty_string_for_empty_json_array(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'phone' => '[]',
        ]);

        $this->assertEquals('', $customer->phone);
    }

    public function test_get_email_attribute_returns_string_value(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('test@example.com', $customer->email);
    }

    public function test_get_email_attribute_returns_first_element_of_json_string(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'email' => '["test@example.com","other@example.com"]',
        ]);

        $this->assertEquals('test@example.com', $customer->email);
    }

    public function test_get_first_name_attribute_returns_string_value(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'first_name' => 'John',
        ]);

        $this->assertEquals('John', $customer->first_name);
    }

    public function test_get_first_name_attribute_returns_first_element_of_json_string(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'first_name' => '["John","Jane"]',
        ]);

        $this->assertEquals('John', $customer->first_name);
    }

    public function test_get_last_name_attribute_returns_string_value(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('Doe', $customer->last_name);
    }

    public function test_get_last_name_attribute_returns_first_element_of_json_string(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
            'last_name' => '["Doe","Smith"]',
        ]);

        $this->assertEquals('Doe', $customer->last_name);
    }

    public function test_customer_belongs_to_company(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(\App\Models\Company::class, $customer->company);
    }

    public function test_customer_belongs_to_user(): void
    {
        $customer = Customer::factory()->create([
            'user_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(\App\Models\User::class, $customer->user);
    }
}
