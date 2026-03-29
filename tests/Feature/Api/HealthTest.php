<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_check_returns_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    public function test_health_check_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'app',
                'timestamp',
            ]);
    }

    public function test_health_check_returns_correct_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'API is healthy!',
            ]);
    }

    public function test_health_check_returns_app_name(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'app' => 'Hello from SwiftStock API',
            ]);
    }

    public function test_health_check_returns_timestamp(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
            ]);

        $this->assertNotEmpty($response->json('timestamp'));
    }

    public function test_health_check_accessible_without_authentication(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    public function test_health_post_method_not_allowed(): void
    {
        $response = $this->postJson('/api/health');

        $response->assertStatus(405);
    }

    public function test_health_delete_method_not_allowed(): void
    {
        $response = $this->deleteJson('/api/health');

        $response->assertStatus(405);
    }
}
