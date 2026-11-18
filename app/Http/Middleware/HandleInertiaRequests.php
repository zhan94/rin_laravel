<?php

namespace App\Http\Middleware;

use App\Enums\RoleKey;
use App\Support\Localization\LocalizedText;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $roleKey = $user?->resolvedRoleKey()?->value;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => (int) $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'firstname' => $user->firstname,
                    'surname' => $user->surname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'role_key' => $roleKey,
                ] : null,
                'permissions' => [
                    'manageBookings' => $user?->canAccessRole(RoleKey::MANAGER) ?? false,
                    'readCustomerData' => $user?->canAccessRole(RoleKey::GROUP_LEADER) ?? false,
                    'users' => $user?->canAccessRole(RoleKey::ADMIN) ?? false,
                    'logs' => $user?->canAccessRole(RoleKey::ADMIN) ?? false,
                    'issues' => $user?->canAccessRole(RoleKey::GROUP_LEADER) ?? false,
                    'horizon' => $user?->canAccessRole(RoleKey::ADMIN) ?? false,
                ],
            ],
            'locale' => [
                'current' => LocalizedText::currentLocale(),
                'fallback' => LocalizedText::fallbackLocale(),
                'available' => LocalizedText::supportedLocales(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
