<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCurrentStaffAccount
{
    public const SESSION_KEY = 'staff_authentication_stamp';

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return $next($request);
        }

        /** @var PersonalAccessToken|TransientToken|null $token */
        $token = $user->currentAccessToken();
        if ($request->is('api/*') && ! $request->hasSession() && ! $token instanceof PersonalAccessToken) {
            throw new AuthenticationException;
        }

        $guard = Auth::guard('web');
        $session = $guard instanceof SessionGuard && $request->hasSession()
            && $request->session()->has($guard->getName());
        $stamp = $session ? $request->session()->get(self::SESSION_KEY) : null;

        if (! $user->maySignIn() || ($session && (! is_string($stamp) || ! hash_equals($user->authenticationStamp(), $stamp)))) {
            if ($session) {
                $guard->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            throw new AuthenticationException;
        }

        if ($token instanceof PersonalAccessToken && ! $request->routeIs('api.v1.auth.token.destroy')) {
            $ability = $request->isMethodSafe() ? 'crm:read' : 'crm:write';
            abort_unless($user->tokenCan($ability), 403);
        }

        return $next($request);
    }
}
