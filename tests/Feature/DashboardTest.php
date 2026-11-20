<?php

namespace Tests\Feature;

use App\DTOs\Bookings\BookingData;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_uses_live_customers_bookings_and_unique_payment_totals(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 2));
        $this->actingAs(User::factory()->create());
        Customer::factory()->create(['active' => false]);
        $service = Service::factory()->create(['title' => 'Synthetic lesson', 'max' => 10]);
        $location = Location::factory()->create(['service_id' => 0]);
        app(BookingService::class)->create(BookingData::fromArray([
            'firstname' => 'Synthetic',
            'skill_id' => 1,

            'lastname' => 'Customer',
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-02', '2026-12-03'],
            'sum_paid' => 100.25,
            'paid' => 20.1,
        ]));

        $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('season.start', '2026-09-01')
            ->where('today', '2026-12-02')
            ->where('customers', ['total' => 2, 'active' => 1])
            ->where('stats', ['records' => 1, 'clients' => 1, 'dates' => 2, 'sum' => 100.25, 'paid' => 20.1, 'due' => 80.15, 'today' => 1])
            ->where('daily.0.customer', 'Synthetic Customer')
            ->where('byService.0.dates', 2)
        );
    }
}
