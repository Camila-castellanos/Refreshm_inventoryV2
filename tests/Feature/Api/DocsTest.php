<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function test_docs_returns_200(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200);
    }

    public function test_docs_returns_endpoints_structure(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'endpoints',
                'authentication',
            ]);
    }

    public function test_docs_includes_login_endpoint(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'endpoints' => [
                    'POST /api/login',
                ],
            ]);
    }

    public function test_docs_includes_health_endpoint(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'endpoints' => [
                    'GET /api/health',
                ],
            ]);
    }

    public function test_docs_includes_authentication_info(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'authentication' => [
                    'Use header',
                    'Obtain token via',
                ],
            ]);
    }

    public function test_docs_includes_items_endpoint_info(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertArrayHasKey('Items resource (protected by Bearer Token)', $data['endpoints'] ?? []);
    }

    public function test_docs_is_accessible_without_authentication(): void
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200);
    }
}
