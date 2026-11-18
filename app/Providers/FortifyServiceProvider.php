<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\PasswordResetLinkResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        ResetPassword::createUrlUsing(static fn (User $user, string $token): string => rtrim((string) config('app.url'), '/').route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], absolute: false));
    }

    /**
     * Authenticate staff using a supported password hash.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $identifier = trim((string) $request->input(Fortify::username(), ''));
            $password = (string) $request->input('password', '');

            if ($identifier === '' || $password === '') {
                return null;
            }

            $user = $this->findUserForIdentifier($identifier);
            if (! $user instanceof User || ! $user->maySignIn()) {
                return null;
            }

            $storedPassword = (string) ($user->password ?? '');
            if (! password_verify($password, $storedPassword)) {
                return null;
            }

            if (Hash::needsRehash($storedPassword)) {
                DB::transaction(fn () => $user->forceFill(['password' => $password])->save());
            }

            return $user;
        });
    }

    private function findUserForIdentifier(string $identifier): ?User
    {
        $usernameField = Fortify::username();

        $user = User::query()
            ->where($usernameField, $identifier)
            ->first();

        if (! $user instanceof User && $usernameField !== 'username') {
            $user = User::query()->where('username', $identifier)->first();
        }

        if (! $user instanceof User && $usernameField !== 'email') {
            $user = User::query()->where('email', $identifier)->first();
        }

        return $user instanceof User ? $user : null;
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('passkey', function (Request $request): array {
            $identifier = Str::lower(trim($request->string('username', $request->string('email')->toString())->toString()));

            return [
                Limit::perMinute(12)->by('passkey:network:'.$request->ip()),
                Limit::perMinute(5)->by('passkey:identity:'.hash('sha256', $identifier.'|'.$request->ip())),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
