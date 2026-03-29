<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    public function test_login_successful_returns_user_and_token(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role', 'company_id'],
                'token',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_with_invalid_email_format_returns_422(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['email'],
            ]);
    }

    public function test_login_without_email_returns_422(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['email'],
            ]);
    }

    public function test_login_without_password_returns_422(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['password'],
            ]);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_login_with_nonexistent_user_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_login_returns_sanctum_token(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    public function test_login_returns_user_data(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
            'name' => 'Test User',
            'role' => 'USER',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => $user->email,
                    'role' => 'USER',
                ],
            ]);
    }

    public function test_login_multiple_users_same_company_different_tokens(): void
    {
        $user1 = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
        ]);

        $user2 = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
        ]);

        $response1 = $this->postJson('/api/login', [
            'email' => $user1->email,
            'password' => 'password123',
        ]);

        $response2 = $this->postJson('/api/login', [
            'email' => $user2->email,
            'password' => 'password123',
        ]);

        $token1 = $response1->json('token');
        $token2 = $response2->json('token');

        $this->assertNotEquals($token1, $token2);
    }

    public function test_login_email_must_be_valid_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalidemail',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email field must be a valid email address.');
    }

    public function test_login_empty_request_returns_validation_errors(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => ['email', 'password'],
            ]);
    }

    public function test_get_login_returns_405_method_not_allowed(): void
    {
        $response = $this->getJson('/api/login');

        $response->assertStatus(405)
            ->assertJson([
                'error' => 'Method not allowed',
            ]);
    }

    public function test_login_same_user_creates_different_tokens(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
        ]);

        $response1 = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response2 = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token1 = $response1->json('token');
        $token2 = $response2->json('token');

        $this->assertNotEquals($token1, $token2);
    }

    public function test_login_creates_token_with_correct_name(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $token = $response->json('token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'api-token',
        ]);
    }

    public function test_login_with_different_user_roles(): void
    {
        $owner = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'OWNER',
            'password' => bcrypt('password123'),
        ]);

        $admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'ADMIN',
            'password' => bcrypt('password123'),
        ]);

        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'USER',
            'password' => bcrypt('password123'),
        ]);

        $responseOwner = $this->postJson('/api/login', [
            'email' => $owner->email,
            'password' => 'password123',
        ]);

        $responseAdmin = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $responseUser = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $responseOwner->assertStatus(200);
        $responseAdmin->assertStatus(200);
        $responseUser->assertStatus(200);

        $this->assertEquals('OWNER', $responseOwner->json('user.role'));
        $this->assertEquals('ADMIN', $responseAdmin->json('user.role'));
        $this->assertEquals('USER', $responseUser->json('user.role'));
    }
}
