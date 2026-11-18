<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Actions\Fortify\ResetUserPassword;
use App\Enums\RoleKey;
use App\Http\Middleware\EnsureCurrentStaffAccount;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\TestCase;

final class StaffBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_assign_or_modify_superior_roles(): void
    {
        $admin = $this->staff(RoleKey::ADMIN);
        $supervisor = $this->staff(RoleKey::SUPER_ADMIN);
        $manager = $this->staff(RoleKey::MANAGER);
        $payload = [
            'username' => 'synthetic-review',
            'firstname' => 'Synthetic',
            'lastname' => 'Reviewer',
            'email' => 'synthetic-review@example.test',
            'password' => 'Unique synthetic review password',
            'role_id' => $supervisor->role_id,
        ];

        $this->actingAs($admin)->postJson('/api/v1/users', $payload)->assertForbidden();
        $this->putJson('/api/v1/users/'.$manager->id, $payload)->assertForbidden();
        $this->putJson('/api/v1/users/'.$supervisor->id, $payload)->assertForbidden();
        $this->deleteJson('/api/v1/users/'.$supervisor->id)->assertForbidden();
        self::assertSame($admin->role_id, $admin->fresh()?->role_id);
        self::assertNotNull($supervisor->fresh());
    }

    public function test_denied_staff_request_emits_only_redacted_audit_metadata(): void
    {
        $driver = $this->staff(RoleKey::DRIVER);
        config(['logging.channels.staff_security' => [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
        ]]);

        $this->actingAs($driver)->postJson('/api/v1/customers', ['email' => 'private-fixture@example.test'])->assertForbidden();

        $channel = Log::channel('staff_security');
        self::assertInstanceOf(\Illuminate\Log\Logger::class, $channel);
        $logger = $channel->getLogger();
        self::assertInstanceOf(Logger::class, $logger);
        $handler = $logger->getHandlers()[0];
        self::assertInstanceOf(TestHandler::class, $handler);
        [$record] = $handler->getRecords();
        self::assertSame('staff.authorization.denied', $record->message);
        self::assertSame(['event_id', 'staff_id', 'subject_id', 'route'], array_keys($record->context));
        self::assertSame($driver->id, $record->context['staff_id']);
        self::assertNull($record->context['subject_id']);
    }

    public function test_staff_routes_enforce_reference_roles_in_each_context(): void
    {
        $driver = $this->staff(RoleKey::DRIVER);
        $this->actingAs($driver)->get('/crm/customers')->assertForbidden();
        $this->getJson('/api/v1/customers')->assertForbidden();
        $this->postJson('/api/v1/cards')->assertForbidden();
        $this->getJson('/api/v1/reports')->assertOk();

        $leader = $this->staff(RoleKey::GROUP_LEADER);
        $this->actingAs($leader)->getJson('/api/v1/customers')->assertOk();
        $this->postJson('/api/v1/customers')->assertForbidden();
    }

    public function test_disabled_and_invalid_credentials_cannot_open_sessions(): void
    {
        $user = $this->staff(RoleKey::ADMIN);
        $this->post('/login', [
            'email' => $user->email, 'password' => $user->getAuthPassword(),
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->update(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_credential_change_invalidates_an_existing_session_and_tokens(): void
    {
        $user = $this->staff(RoleKey::MANAGER);
        $token = $user->createToken('Existing fixture')->plainTextToken;
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $this->withSession([
            $guard->getName() => $user->id,
            EnsureCurrentStaffAccount::SESSION_KEY => $user->authenticationStamp(),
        ])->get('/dashboard')->assertOk();

        app(ResetUserPassword::class)->reset($user, [
            'password' => 'A new synthetic password!',
            'password_confirmation' => 'A new synthetic password!',
        ]);
        Auth::forgetGuards();

        $this->get('/dashboard')->assertRedirect('/login');
        $this->withToken($token)->getJson('/api/v1/reports')->assertUnauthorized();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_disabled_accounts_and_roles_are_rejected_for_bearer_tokens(): void
    {
        $user = $this->staff(RoleKey::MANAGER);
        $token = $user->createToken('Existing fixture')->plainTextToken;
        Role::query()->whereKey($user->role_id)->update(['is_active' => false]);

        $this->withToken($token)->getJson('/api/v1/reports')->assertUnauthorized();
    }

    public function test_cookie_mutations_require_csrf_and_untrusted_origins_do_not_get_sessions(): void
    {
        config(['sanctum.stateful' => ['app.example.test']]);
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, VerifiedCsrf::class);
        $user = $this->staff(RoleKey::MANAGER);
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');
        $this->withSession([
            $guard->getName() => $user->id,
            EnsureCurrentStaffAccount::SESSION_KEY => $user->authenticationStamp(),
        ]);

        $this->withHeader('Origin', 'https://app.example.test')
            ->postJson('/api/v1/cards')->assertStatus(419);
        Auth::forgetGuards();
        $this->withHeader('Origin', 'https://other.example.test')
            ->postJson('/api/v1/cards')->assertUnauthorized();
    }

    public function test_framing_is_disabled_and_unused_public_assets_are_removed(): void
    {
        $this->get('/login')->assertOk()->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        self::assertFileDoesNotExist(public_path('img/manual/reports.png'));
        self::assertFileDoesNotExist(public_path('css/styles/js/bootstrap.js'));
    }

    public function test_recovery_responses_do_not_disclose_account_existence(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = $this->staff(RoleKey::ADMIN);

        $existing = $this->postJson('/forgot-password', ['email' => $user->email])->assertOk();
        $unknown = $this->postJson('/forgot-password', ['email' => 'unlisted@example.test'])->assertOk();
        self::assertSame($existing->json('message'), $unknown->json('message'));
    }

    public function test_passkey_origins_are_configured_and_login_is_rate_limited(): void
    {
        config(['webauthn.origins' => 'https://crm.example.test']);
        $method = new \ReflectionMethod(\App\Services\Auth\PasskeyOptionsService::class, 'getAllowedOrigins');
        self::assertSame(['https://crm.example.test'], $method->invoke(app(\App\Services\Auth\PasskeyOptionsService::class)));

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/passkeys/login/options', ['username' => 'missing-demo-staff'])->assertStatus(400);
        }
        $this->postJson('/passkeys/login/options', ['username' => 'missing-demo-staff'])->assertTooManyRequests();
    }

    public function test_issue_upload_rolls_back_the_file_when_metadata_cannot_be_saved(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->actingAs($this->staff(RoleKey::GROUP_LEADER));
        \Illuminate\Support\Facades\DB::unprepared("CREATE TRIGGER reject_demo_upload BEFORE INSERT ON issue_report_attachments BEGIN SELECT RAISE(ABORT, 'Fixture metadata write failed'); END");

        $this->post('/api/v1/issues', [
            'module_key' => 'booking', 'issue_key' => 'cannot_save', 'priority' => 'normal',
            'description' => 'An isolated synthetic attachment regression.',
            'screenshots' => [$this->pngUpload('sample.png')],
        ], ['Accept' => 'application/json'])->assertStatus(500);

        $this->assertDatabaseCount('issue_reports', 0);
        self::assertSame([], \Illuminate\Support\Facades\Storage::disk('local')->allFiles());
    }

    private function staff(RoleKey $key): User
    {
        $role = Role::query()->firstOrCreate(['role_key' => $key], RoleCatalog::payload($key));

        return User::factory()->for($role)->create();
    }
}

final class VerifiedCsrf extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
