<?php

use App\Http\Controllers\Auth\PasskeyOptionsController;
use App\Http\Controllers\DashboardController;
use App\Support\Localization\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/', function (Request $request) {
    if ($request->user() !== null) {
        return to_route($request->user()->canAccessRole(\App\Enums\RoleKey::MANAGER) ? 'crm.bookings.index' : 'dashboard');
    }

    return Inertia::render('auth/Login', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'status' => $request->session()->get('status'),
    ]);
})->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::post('passkeys/login/options', [PasskeyOptionsController::class, 'loginOptions'])->middleware('throttle:passkey')->name('passkeys.login.options');
Route::post('passkeys/login/verify', [PasskeyOptionsController::class, 'loginVerify'])->middleware('throttle:passkey')->name('passkeys.login.verify');

Route::middleware(['auth'])->group(function (): void {
    Route::post('passkeys/register/options', [PasskeyOptionsController::class, 'registerOptions'])->middleware('password.confirm')->name('passkeys.register.options');
    Route::post('passkeys/register/verify', [PasskeyOptionsController::class, 'registerVerify'])->middleware('password.confirm')->name('passkeys.register.verify');
    Route::post('passkeys/list', [PasskeyOptionsController::class, 'list'])->name('passkeys.list');
    Route::post('passkeys/delete', [PasskeyOptionsController::class, 'delete'])->middleware('password.confirm')->name('passkeys.delete');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('manual/{locale?}', function (?string $locale = null): BinaryFileResponse {
        $requestedLocale = LocalizedText::currentLocale($locale);
        /** @var array<string,string> $manualFiles */
        $manualFiles = config('localization.manual_files', []);

        $candidates = array_values(array_unique(array_filter([
            $requestedLocale,
            app()->getLocale(),
            config('app.fallback_locale'),
            'en',
        ], static fn (mixed $value): bool => is_string($value) && trim($value) !== '')));

        $path = null;

        foreach ($candidates as $candidate) {
            $filename = $manualFiles[$candidate] ?? null;
            if ($filename === null) {
                continue;
            }

            $candidatePath = resource_path('manual/'.$filename);
            if (is_file($candidatePath)) {
                $path = $candidatePath;

                break;
            }
        }

        abort_unless($path !== null && is_file($path), 404);

        return response()->file($path);
    })->where('locale', implode('|', LocalizedText::supportedLocales()))->name('manual.show');
});

Route::middleware(['auth', 'verified'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function (): void {
        Route::inertia('bookings', 'crm/BookingsIndex')->middleware('role:manager')->name('bookings.index');
        Route::inertia('bonus', 'crm/BonusIndex')->middleware('role:manager')->name('bonus.index');
        Route::inertia('cards', 'crm/CardsIndex')->middleware('role:manager')->name('cards.index');
        Route::inertia('customers', 'crm/CustomersIndex')->middleware('role:group_leader')->name('customers.index');
        Route::inertia('equips', 'crm/EquipsIndex')->middleware('role:manager')->name('equips.index');
        Route::inertia('issues', 'crm/IssuesIndex')->middleware('role:group_leader')->name('issues.index');
        Route::inertia('locations', 'crm/LocationsIndex')->middleware('role:manager')->name('locations.index');
        Route::inertia('logs', 'crm/LogsIndex')->middleware('role:admin')->name('logs.index');
        Route::inertia('reports', 'crm/ReportsIndex')->name('reports.index');
        Route::inertia('seasons', 'crm/SeasonsIndex')->middleware('role:group_leader')->name('seasons.index');
        Route::inertia('services', 'crm/ServicesIndex')->middleware('role:group_leader')->name('services.index');
        Route::inertia('services/periods/create', 'crm/ServicePeriodCreate')->middleware('role:manager')->name('services.periods.create');
        Route::inertia('users', 'crm/UsersIndex')->middleware('role:admin')->name('users.index');
    });

require __DIR__.'/settings.php';
