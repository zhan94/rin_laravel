<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\BonusController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\BookingPaymentController;
use App\Http\Controllers\Api\V1\BookingSupportController;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\EquipController;
use App\Http\Controllers\Api\V1\IssueController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\LogController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SeasonController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\ServiceSlotController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::post('auth/token', [AuthTokenController::class, 'store'])
            ->middleware(['auth:web', 'staff', 'throttle:api', 'password.confirm'])
            ->name('auth.token.store');

        Route::middleware(['auth:sanctum', 'staff', 'verified', 'throttle:api', 'role:driver'])->group(function (): void {
            Route::delete('auth/token/current', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

            Route::apiResource('bookings', BookingController::class)
                ->middlewareFor(['index', 'show'], 'role:group_leader')
                ->middlewareFor(['store', 'update', 'destroy'], 'role:manager');
            Route::post('bookings/{booking}/payments/validate', [BookingPaymentController::class, 'validateAmount'])->middleware('role:manager')->name('bookings.payments.validate');
            Route::post('bookings/{booking}/payments/apply', [BookingPaymentController::class, 'apply'])->middleware('role:manager')->name('bookings.payments.apply');

            Route::prefix('booking-support')->as('booking-support.')->middleware('role:manager')->group(function (): void {
                Route::get('customers/autocomplete', [BookingSupportController::class, 'autocompleteCustomers'])->name('customers.autocomplete');
                Route::get('customers/{customer}', [BookingSupportController::class, 'customer'])->name('customers.show');
                Route::get('services/{service}/options', [BookingSupportController::class, 'serviceOptions'])->name('services.options');
                Route::get('services/{service}/slots', [BookingSupportController::class, 'serviceSlots'])->name('services.slots');
                Route::get('services/{service}/dates', [BookingSupportController::class, 'serviceDates'])->name('services.dates');
                Route::get('services/{service}/availability', [BookingSupportController::class, 'serviceAvailability'])->name('services.availability');
                Route::get('bonus/{bonus}/context', [BookingSupportController::class, 'bonusContext'])->name('bonus.context');
                Route::get('prefill', [BookingSupportController::class, 'prefill'])->name('prefill');
            });

            Route::apiResource('bonus', BonusController::class)->middleware('role:manager');
            Route::apiResource('cards', CardController::class)->middleware('role:manager');
            Route::get('customers/unpaid', [CustomerController::class, 'unpaid'])->middleware('role:group_leader')->name('customers.unpaid');
            Route::apiResource('customers', CustomerController::class)
                ->middlewareFor(['index', 'show'], 'role:group_leader')
                ->middlewareFor(['store', 'update', 'destroy'], 'role:manager');
            Route::apiResource('equips', EquipController::class)->middleware('role:manager');

            Route::get('issues/module-issue-map', [IssueController::class, 'moduleIssueMap'])->name('issues.module-issue-map');
            Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
            Route::post('issues', [IssueController::class, 'store'])->name('issues.store');
            Route::get('issues/attachments/{attachment}', [IssueController::class, 'attachment'])->name('issues.attachments.download');
            Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
            Route::patch('issues/{issue}/status', [IssueController::class, 'updateStatus'])->name('issues.status');
            Route::patch('issues/{issue}/assignee', [IssueController::class, 'updateAssignee'])->name('issues.assignee');
            Route::post('issues/{issue}/comments', [IssueController::class, 'storeComment'])->name('issues.comments.store');

            Route::apiResource('locations', LocationController::class)->middleware('role:manager');

            Route::get('logs', [LogController::class, 'index'])->name('logs.index');
            Route::delete('logs/{log}', [LogController::class, 'destroy'])->name('logs.destroy');
            Route::post('logs/delete-selected', [LogController::class, 'deleteSelected'])->name('logs.delete-selected');
            Route::delete('logs', [LogController::class, 'clear'])->name('logs.clear');

            Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
            Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

            Route::put('seasons/defaults', [SeasonController::class, 'updateDefaults'])->middleware('role:manager')->name('seasons.defaults.update');
            Route::get('seasons', [SeasonController::class, 'index'])->middleware('role:group_leader')->name('seasons.index');
            Route::post('seasons', [SeasonController::class, 'store'])->middleware('role:manager')->name('seasons.store');
            Route::put('seasons/{season}', [SeasonController::class, 'update'])->middleware('role:manager')->name('seasons.update');
            Route::delete('seasons/{season}', [SeasonController::class, 'destroy'])->middleware('role:manager')->name('seasons.destroy');

            Route::apiResource('services', ServiceController::class)
                ->middlewareFor(['index', 'show'], 'role:group_leader')
                ->middlewareFor(['store', 'update', 'destroy'], 'role:manager');
            Route::apiResource('services.slots', ServiceSlotController::class)->shallow()->middlewareFor(['index', 'show'], 'role:group_leader')->middlewareFor(['store', 'update', 'destroy'], 'role:manager');
            Route::apiResource('users', UserController::class)
                ->middleware('role:admin')
                ->missing(fn () => \App\Support\Api\ApiResponseFactory::error('user_not_found', 'User not found.', 404));
        });
    });
