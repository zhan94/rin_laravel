<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureCurrentStaffAccount;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Support\Auth\AuthenticatedUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Password');
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = AuthenticatedUserResolver::fromRequest($request);
        DB::transaction(fn () => $user->update(['password' => $request->validated('password')]));
        $request->session()->regenerate();
        $request->session()->put(EnsureCurrentStaffAccount::SESSION_KEY, $user->authenticationStamp());

        return back();
    }
}
