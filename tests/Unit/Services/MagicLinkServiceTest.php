<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Services\MagicLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MagicLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    private MagicLinkService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MagicLinkService();
    }

    public function test_generates_valid_token(): void
    {
        $customer = Customer::factory()->create();

        $token = $this->service->generateToken($customer);

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    public function test_validates_valid_token(): void
    {
        $customer = Customer::factory()->create();
        $token = $this->service->generateToken($customer);

        $validatedCustomer = $this->service->validateToken($token);

        $this->assertNotNull($validatedCustomer);
        $this->assertEquals($customer->id, $validatedCustomer->id);
    }

    public function test_consume_invalidates_token(): void
    {
        $customer = Customer::factory()->create();
        $token = $this->service->generateToken($customer);

        // Consume the token
        $this->service->consumeToken($customer);

        // Token should no longer be valid
        $validatedCustomer = $this->service->validateToken($token);
        $this->assertNull($validatedCustomer);
    }

    public function test_token_expires_after_15_minutes(): void
    {
        $customer = Customer::factory()->create([
            'remember_token' => 'expired-token',
            'magic_link_expires_at' => now()->subMinutes(1),
        ]);

        $validatedCustomer = $this->service->validateToken('expired-token');

        $this->assertNull($validatedCustomer);
    }

    public function test_invalid_token_returns_null(): void
    {
        $validatedCustomer = $this->service->validateToken('non-existent-token');

        $this->assertNull($validatedCustomer);
    }

    public function test_send_magic_link_creates_token_and_sends_email(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create();

        $this->service->sendMagicLink($customer, 'https://example.com/magic-link/test-token');

        Mail::assertSent(\App\Mail\MagicLinkEmail::class);
    }
}