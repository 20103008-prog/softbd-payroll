<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_employees_requires_authentication(): void
    {
        $this->getJson('/api/employees')->assertUnauthorized();
    }

    public function test_login_issues_token_and_allows_access(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'api@example.com',
            'password' => 'password',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'token_type', 'user']);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/employees')
            ->assertOk();
    }

    public function test_zkteco_push_stays_public(): void
    {
        $this->postJson('/api/zkteco/attendance', [
            'ip_address' => '192.168.1.1',
            'port' => 4370,
            'logs' => [],
        ])->assertStatus(422); // validation, not 401
    }
}
