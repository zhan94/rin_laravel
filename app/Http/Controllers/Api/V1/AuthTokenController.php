<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthTokenController extends Controller
{
    use InteractsWithApiResponses;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['device_name' => ['nullable', 'string', 'max:80']]);
        $user = $request->user('web');
        abort_unless($user instanceof User, 401);

        $expiresAt = now()->addMinutes((int) config('sanctum.expiration'));
        $token = $user->createToken($validated['device_name'] ?? 'CRM API access', ['crm:read', 'crm:write'], $expiresAt);

        return $this->success(data: [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
        ])->withHeaders(['Cache-Control' => 'no-store']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user instanceof User ? $user->currentAccessToken() : null;
        abort_unless($token instanceof PersonalAccessToken, 403);
        $token->delete();

        return $this->success(data: ['revoked' => true]);
    }
}
