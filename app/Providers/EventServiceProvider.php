<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BookingCreated;
use App\Http\Middleware\EnsureCurrentStaffAccount;
use App\Listeners\SendBookingEmail;
use App\Models\User;
use App\Support\Auth\StaffAudit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        BookingCreated::class => [
            SendBookingEmail::class,
        ],
    ];

    public function boot(): void
    {
        Event::listen(Failed::class, static function (Failed $event): void {
            StaffAudit::record('staff.sign_in.denied', $event->user instanceof User ? $event->user->id : null);
        });
        Event::listen(PasswordReset::class, static function (PasswordReset $event): void {
            StaffAudit::record('staff.password_reset.completed', $event->user instanceof User ? $event->user->id : null);
        });
        Event::listen(Login::class, static function (Login $event): void {
            if ($event->guard === 'web' && $event->user instanceof User && request()->hasSession()) {
                request()->session()->put(EnsureCurrentStaffAccount::SESSION_KEY, $event->user->authenticationStamp());
            }
        });
    }
}
