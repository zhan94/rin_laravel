<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasskeyOptionsService;
use App\Support\Auth\StaffAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PasskeyOptionsController extends Controller
{
    public function __construct(
        private readonly PasskeyOptionsService $service,
    ) {}

    public function loginOptions(Request $request): JsonResponse
    {
        $identifier = trim((string) $request->input('username', $request->input('email', '')));

        if ($identifier === '') {
            return response()->json([
                'ok' => false,
                'error' => 'Username is required for passkey login.',
            ], 422);
        }

        try {
            return response()->json([
                'ok' => true,
                'publicKey' => $this->service->createAuthenticationOptions($identifier),
            ]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid login request.',
            ], 400);
        }
    }

    public function loginVerify(Request $request): JsonResponse
    {
        try {
            $user = $this->service->verifyAuthentication($request->all());
            abort_unless($user->maySignIn(), 401);

            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            $intended = $request->session()->pull('url.intended');
            $redirect = $this->sanitizeRedirectPath(is_string($intended) ? $intended : null);

            return response()->json([
                'ok' => true,
                'redirect' => $redirect,
            ]);
        } catch (Throwable) {
            StaffAudit::record('staff.passkey_sign_in.denied');

            return response()->json([
                'ok' => false,
                'error' => 'Invalid login request.',
            ], 401);
        }
    }

    private function sanitizeRedirectPath(?string $candidate): string
    {
        $fallback = route('dashboard', absolute: false);

        if ($candidate === null || $candidate === '') {
            return $fallback;
        }

        if (str_starts_with($candidate, '/') && ! str_starts_with($candidate, '//') && ! str_contains($candidate, '\\')) {
            return $candidate;
        }
        if (str_starts_with($candidate, '//') || str_contains($candidate, '\\')) {
            return $fallback;
        }

        $parts = parse_url($candidate);
        if (! is_array($parts)) {
            return $fallback;
        }

        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        if (str_starts_with($path, '//') || preg_match('/[\x00-\x1f\x7f]/', $path) === 1) {
            return $fallback;
        }

        $query = (string) ($parts['query'] ?? '');
        if ($query !== '') {
            $path .= '?'.$query;
        }

        return $path;
    }

    public function registerOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'ok' => false,
                'error' => 'Access denied.',
            ], 401);
        }

        try {
            return response()->json([
                'ok' => true,
                'publicKey' => $this->service->createRegistrationOptions($user),
            ]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'error' => 'Unable to complete the passkey request.',
            ], 400);
        }
    }

    public function registerVerify(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'ok' => false,
                'error' => 'Access denied.',
            ], 401);
        }

        try {
            $this->service->verifyRegistration($user, $request->all());

            return response()->json([
                'ok' => true,
                'message' => 'Passkey added.',
            ]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'error' => 'Unable to complete the passkey request.',
            ], 400);
        }
    }

    public function list(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'ok' => false,
                'error' => 'Access denied.',
            ], 401);
        }

        try {
            return response()->json([
                'ok' => true,
                'rows' => $this->service->listPasskeys($user),
            ]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'error' => 'Unable to complete the passkey request.',
            ], 400);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json([
                'ok' => false,
                'error' => 'Access denied.',
            ], 401);
        }

        $passkeyId = (int) $request->input('id', 0);
        if ($passkeyId <= 0) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid passkey id.',
            ], 422);
        }

        try {
            $deleted = $this->service->deletePasskey($user, $passkeyId);
            if (! $deleted) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Passkey not found.',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'message' => 'Passkey removed.',
            ]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'error' => 'Unable to complete the passkey request.',
            ], 400);
        }
    }
}
