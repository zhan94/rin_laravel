<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\PasskeyOptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PasskeyOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_options_do_not_force_an_rp_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])->postJson('/passkeys/register/options');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('publicKey.rp.name', config('app.name'))
            ->assertJsonMissingPath('publicKey.rp.id');
    }

    public function test_login_options_do_not_force_an_rp_id(): void
    {
        $user = User::factory()->create([
            'username' => 'jan',
        ]);

        DB::table('user_passkeys')->insert([
            'user_id' => $user->id,
            'credential_id' => random_bytes(32),
            'public_key' => random_bytes(64),
            'sign_count' => 0,
            'device_name' => 'MacBook',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/passkeys/login/options', [
            'username' => $user->username,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonMissingPath('publicKey.rpId');
    }

    public function test_passkey_login_verify_normalizes_cross_host_intended_redirect_to_relative(): void
    {
        $user = User::factory()->create();

        $this->app->instance(PasskeyOptionsService::class, new class($user) extends PasskeyOptionsService
        {
            public function __construct(
                private readonly User $user,
            ) {}

            public function verifyAuthentication(array $payload): User
            {
                return $this->user;
            }
        });

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->withSession(['url.intended' => 'http://ski2.0.test/crm/bookings'])
            ->postJson('/passkeys/login/verify', [
                'credential_id' => 'ignored',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', '/crm/bookings');

        $this->assertAuthenticatedAs($user);
    }

    public function test_passkey_login_verify_converts_same_host_absolute_redirect_to_relative(): void
    {
        $user = User::factory()->create();

        $this->app->instance(PasskeyOptionsService::class, new class($user) extends PasskeyOptionsService
        {
            public function __construct(
                private readonly User $user,
            ) {}

            public function verifyAuthentication(array $payload): User
            {
                return $this->user;
            }
        });

        $response = $this
            ->withSession(['url.intended' => 'http://localhost/crm/bookings?tab=active'])
            ->postJson('/passkeys/login/verify', [
                'credential_id' => 'ignored',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', '/crm/bookings?tab=active');

        $this->assertAuthenticatedAs($user);
    }
}
