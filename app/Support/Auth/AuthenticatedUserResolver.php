<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AuthenticatedUserResolver
{
    public static function fromRequest(Request $request): User
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $user;
        }

        throw new AccessDeniedHttpException;
    }
}
