<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\Bonus\BonusRepositoryInterface;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use App\Repositories\Contracts\Bookings\BookingSupportRepositoryInterface;
use App\Repositories\Contracts\Catalog\CardRepositoryInterface;
use App\Repositories\Contracts\Catalog\EquipRepositoryInterface;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\Contracts\Issues\IssueRepositoryInterface;
use App\Repositories\Contracts\Locations\LocationRepositoryInterface;
use App\Repositories\Contracts\Reports\ReportRepositoryInterface;
use App\Repositories\Contracts\Seasons\SeasonRepositoryInterface;
use App\Repositories\Contracts\Services\ServiceRepositoryInterface;
use App\Repositories\Contracts\Services\ServiceSlotRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\Bonus\BonusRepository;
use App\Repositories\Eloquent\Bookings\BookingRepository;
use App\Repositories\Eloquent\Bookings\BookingSupportRepository;
use App\Repositories\Eloquent\Catalog\CardRepository;
use App\Repositories\Eloquent\Catalog\EquipRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\Issues\IssueRepository;
use App\Repositories\Eloquent\Locations\LocationRepository;
use App\Repositories\Eloquent\Reports\ReportRepository;
use App\Repositories\Eloquent\Seasons\SeasonRepository;
use App\Repositories\Eloquent\Services\ServiceRepository;
use App\Repositories\Eloquent\Services\ServiceSlotRepository;
use App\Repositories\Eloquent\UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(BookingSupportRepositoryInterface::class, BookingSupportRepository::class);
        $this->app->bind(BonusRepositoryInterface::class, BonusRepository::class);
        $this->app->bind(CardRepositoryInterface::class, CardRepository::class);
        $this->app->bind(EquipRepositoryInterface::class, EquipRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(IssueRepositoryInterface::class, IssueRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(SeasonRepositoryInterface::class, SeasonRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(ServiceSlotRepositoryInterface::class, ServiceSlotRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(120)->by((string) $key);
        });
    }
}
