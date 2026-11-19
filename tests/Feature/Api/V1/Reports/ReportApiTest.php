<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Reports;

use App\Enums\RoleKey;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\BookingWinterDetail;
use App\Models\Customer;
use App\Models\Equip;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_reports_with_totals_and_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create(['title' => 'Ski School', 'title_en' => 'Ski School']);
        $slot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'date_start' => '2026-12-01',
            'date_end' => '2026-12-31',
        ]);

        $dateOne = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-10',
        ]);
        $dateTwo = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-12',
        ]);

        $customer = Customer::factory()->create([
            'firstname' => 'Mira',
            'surname' => null,
            'lastname' => 'Petrova',
            'date_birth' => '2012-05-10',
            'parent_name' => 'Ivaylo Petrova',
            'parent_phone' => '+359888123456',
            'email' => 'parent@example.com',
        ]);

        $location = Location::factory()->create([
            'location_from' => 'Base',
            'location_to' => 'Lift',
            'from_time' => '08:15',
            'to_time' => '08:45',
            'service_id' => $service->id,
        ]);

        $equip = Equip::factory()->create([
            'service' => $service->id,
            'title' => 'Ski Set',
            'title_en' => 'Ski Set',
        ]);

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 300,
            'paid' => 120,
            'location' => $location->id,
            'approved' => true,
            'active' => true,
            'lunch' => false,
            'note' => 'Bring warm jacket',
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $dateOne->id,
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $dateTwo->id,
        ]);

        BookingWinterDetail::query()->create([
            'booking_id' => $booking->id,
            'skill_id' => 2,
            'equip_id' => $equip->id,
            'card_id' => null,
            'shoe_size' => null,
            'height' => null,
        ]);

        $response = $this->getJson("/api/v1/reports?from=2026-12-01&to=2026-12-31&service_id={$service->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.is_driver', false)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.summary.sum_total', 300)
            ->assertJsonPath('data.summary.paid_total', 120)
            ->assertJsonPath('data.summary.due_total', 180)
            ->assertJsonPath('data.summary.due_count', 1)
            ->assertJsonPath('data.rows.0.booking_id', $booking->id)
            ->assertJsonPath('data.rows.0.customer_name', 'Mira Petrova')
            ->assertJsonPath('data.rows.0.location', 'Base - Lift (08:15 - 08:45)')
            ->assertJsonPath('data.rows.0.sum_paid', 300)
            ->assertJsonPath('data.rows.0.paid', 120)
            ->assertJsonPath('data.rows.0.due', 180)
            ->assertJsonPath('data.rows.0.equip.title', 'Ski Set')
            ->assertJsonCount(2, 'data.rows.0.dates')
            ->assertJsonPath('errors', []);
    }

    public function test_driver_role_hides_admin_only_columns(): void
    {
        $driverRole = Role::query()->firstOrCreate(
            ['role_key' => RoleKey::DRIVER->value],
            RoleCatalog::payload(RoleKey::DRIVER, 50),
        );

        $driver = User::factory()->create(['role_id' => $driverRole->id]);
        $this->actingAs($driver);

        $service = Service::factory()->create();
        $slot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'date_start' => '2026-12-01',
            'date_end' => '2026-12-31',
        ]);

        $date = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-20',
        ]);

        $customer = Customer::factory()->create([
            'firstname' => 'Niki',
            'lastname' => 'Ivanov',
            'date_birth' => '2014-03-01',
            'email' => 'dispatch-sample@example.test',
            'parent_phone' => '+441632960008',
        ]);

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 200,
            'paid' => 100,
            'location' => Location::factory()->create()->id,
            'approved' => true,
            'active' => true,
            'lunch' => false,
            'note' => "Driver note\n\n[[SKI_META]]\n{\"skill_id\":\"3\",\"equip_enabled\":\"1\",\"shoe_size\":\"\",\"height\":\"\"}\n[[/SKI_META]]",
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $date->id,
        ]);

        $response = $this->getJson('/api/v1/reports?from=2026-12-01&to=2026-12-31&sort=due');

        $response
            ->assertOk()
            ->assertJsonPath('data.is_driver', true)
            ->assertJsonPath('data.filters.sort', 'id')
            ->assertJsonPath('data.rows.0.booking_id', $booking->id)
            ->assertJsonPath('data.rows.0.parent_phone', '+441632960008')
            ->assertJsonPath('data.rows.0.birth', null)
            ->assertJsonPath('data.rows.0.email', null)
            ->assertJsonPath('data.rows.0.note', null)
            ->assertJsonPath('data.rows.0.sum_paid', 0)
            ->assertJsonPath('data.rows.0.paid', 0)
            ->assertJsonPath('data.rows.0.due', 0)
            ->assertJsonPath('data.rows.0.skill', null)
            ->assertJsonPath('data.rows.0.equip', null)
            ->assertJsonPath('data.summary.sum_total', 0)
            ->assertJsonPath('data.summary.paid_total', 0)
            ->assertJsonPath('data.summary.due_total', 0)
            ->assertJsonPath('data.summary.due_count', 1)
            ->assertJsonPath('errors', []);

        $this->getJson('/api/v1/reports?from=2026-12-01&to=2026-12-31&q=dispatch-sample')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_report_filters_require_complete_date_range(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/reports?from=2026-12-01');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'validation_error');
    }
}
