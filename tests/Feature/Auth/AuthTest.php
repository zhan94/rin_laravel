<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_issue_a_scoped_expiring_token_after_password_confirmation(): void
    {
        $user = User::factory()->create();
        config(['sanctum.stateful' => ['app.example.test']]);

        $this->actingAs($user)->withHeader('Origin', 'https://app.example.test')
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/api/v1/auth/token', ['device_name' => 'Isolated fixture'])
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = PersonalAccessToken::query()->sole();
        self::assertSame(['crm:read', 'crm:write'], $token->abilities);
        self::assertNotNull($token->expires_at);
    }

    public function test_staff_cannot_issue_a_token_using_only_a_password(): void
    {
        $user = User::factory()->withTwoFactor()->create();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_issuance_requires_recent_password_confirmation(): void
    {
        config(['sanctum.stateful' => ['app.example.test']]);
        $this->actingAs(User::factory()->create())->withHeader('Origin', 'https://app.example.test')
            ->postJson('/api/v1/auth/token')->assertStatus(423);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_revoke_current_api_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Isolated fixture');

        $this->withToken($token->plainTextToken)
            ->deleteJson('/api/v1/auth/token/current')
            ->assertOk()
            ->assertJsonPath('data.revoked', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
