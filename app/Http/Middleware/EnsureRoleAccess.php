<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoleKey;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        $required = RoleKey::tryFrom(trim($requiredRole));

        if (! $required instanceof RoleKey) {
            throw new AuthorizationException('Access denied.');
        }

        $user = $request->user();
        if (! $user instanceof User || ! $user->canAccessRole($required)) {
            throw new AuthorizationException('Access denied.');
        }

        return $next($request);
    }
}
