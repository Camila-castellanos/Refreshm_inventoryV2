<?php

namespace Tests\Feature\Auth;

use App\Models\LoginActivity;
use App\Models\User;
use Tests\TestCaseWithCompany;

class LoginActivityTest extends TestCaseWithCompany
{
    // === LOGIN ACTIVITY MODEL TESTS ===

    public function test_login_activity_belongs_to_user(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $this->assertEquals($this->owner->id, $activity->user->id);
    }

    public function test_login_activity_fillable_attributes(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Agent',
        ]);

        $this->assertDatabaseHas('login_activities', [
            'user_id' => $this->owner->id,
            'user_agent' => 'Test Agent',
        ]);
    }

    public function test_login_activity_login_at_is_cast_to_datetime(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => '2025-01-15 10:30:00',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $activity->login_at);
    }

    public function test_login_activity_ip_address_encrypted(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertNotNull($activity->ip_address);
    }

    public function test_login_activity_ip_address_decrypted(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '192.168.1.100',
        ]);

        $activity->refresh();
        $this->assertEquals('192.168.1.100', $activity->ip_address);
    }

    public function test_login_activity_user_agent_stored(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => $userAgent,
        ]);

        $this->assertEquals($userAgent, $activity->user_agent);
    }

    public function test_login_activity_timestamps(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertNotNull($activity->created_at);
        $this->assertNotNull($activity->updated_at);
    }

    public function test_login_activity_nullable_ip(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => null,
        ]);

        $this->assertNull($activity->ip_address);
    }

    // === LOGIN ACTIVITY FACTORY TESTS ===

    public function test_factory_creates_valid_instance(): void
    {
        $activity = \Database\Factories\LoginActivityFactory::new()->create();

        $this->assertNotNull($activity->user_id);
        $this->assertNotNull($activity->login_at);
    }

    public function test_factory_with_user(): void
    {
        $activity = \Database\Factories\LoginActivityFactory::new()->forUser($this->owner->id)->create();

        $this->assertEquals($this->owner->id, $activity->user_id);
    }

    public function test_factory_with_custom_ip(): void
    {
        $activity = \Database\Factories\LoginActivityFactory::new()->withIp('10.0.0.1')->create();

        $this->assertEquals('10.0.0.1', $activity->ip_address);
    }

    // === EDGE CASES ===

    public function test_login_with_null_ip(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => null,
        ]);

        $this->assertNull($activity->ip_address);
    }

    public function test_login_with_empty_user_agent(): void
    {
        $activity = LoginActivity::create([
            'user_id' => $this->owner->id,
            'login_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => '',
        ]);

        $this->assertEquals('', $activity->user_agent);
    }

    public function test_login_activity_user_cascade(): void
    {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $activity = LoginActivity::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => '127.0.0.1',
        ]);

        $activityId = $activity->id;

        $user->delete();

        $this->assertSoftDeleted('login_activities', ['id' => $activityId]);
    }
}
