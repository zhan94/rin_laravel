<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Bonus;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_logged_in_user_can_access_customers_api_with_session_cookie(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $response = $this->withHeader('Origin', config('app.url'))->getJson(route('api.v1.customers.index'));

        $response
            ->assertOk()
            ->assertJsonPath('errors', []);
    }

    public function test_authenticated_user_can_list_customers(): void
    {
        $this->actingAs(User::factory()->create());
        Customer::factory()->count(3)->create();

        $response = $this->getJson(route('api.v1.customers.index'));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('errors', []);
    }

    public function test_customer_can_be_created(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = [
            'firstname' => 'Ivan',
            'lastname' => 'Petrov',
            'email' => 'ivan.petrov@example.com',
            'active' => true,
        ];

        $response = $this->postJson(route('api.v1.customers.store'), $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.firstname', 'Ivan')
            ->assertJsonPath('data.lastname', 'Petrov')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('customers', [
            'firstname' => 'Ivan',
            'lastname' => 'Petrov',
        ]);
    }

    public function test_validation_error_uses_standard_envelope(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.customers.store'), []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('meta', [])
            ->assertJsonPath('errors.0.code', 'validation_error')
            ->assertJsonCount(2, 'errors');
    }

    public function test_customer_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create([
            'firstname' => 'Old',
            'lastname' => 'Name',
            'parent_phone' => '111',
        ]);

        $response = $this->putJson(route('api.v1.customers.update', $customer->id), [
            'firstname' => 'New',
            'lastname' => 'Customer',
            'parent_phone' => '222',
            'active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', (int) $customer->id)
            ->assertJsonPath('data.firstname', 'New')
            ->assertJsonPath('data.lastname', 'Customer')
            ->assertJsonPath('data.parent_phone', '222')
            ->assertJsonPath('data.active', false)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'firstname' => 'New',
            'lastname' => 'Customer',
            'parent_phone' => '222',
            'active' => 0,
        ]);
    }

    public function test_customer_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create();

        $response = $this->deleteJson(route('api.v1.customers.destroy', $customer->id));

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_authenticated_user_can_list_unpaid_customers_with_totals(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create(['title_bg' => 'Ski School']);
        $bonus = Bonus::factory()->create(['title_bg' => 'Bus Transfer']);

        $customerA = Customer::factory()->create([
            'firstname' => 'Ani',
            'lastname' => 'Petrova',
        ]);
        $customerB = Customer::factory()->create([
            'firstname' => 'Bobi',
            'lastname' => 'Georgiev',
        ]);

        Booking::query()->create([
            'customer_id' => $customerA->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 300,
            'paid' => 100,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        Booking::query()->create([
            'customer_id' => $customerA->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 200,
            'paid' => 200,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        Booking::query()->create([
            'customer_id' => $customerB->id,
            'service' => $bonus->id,
            'service_type' => Booking::SERVICE_TYPE_BONUS,
            'sum_paid' => 180,
            'paid' => 30,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        $response = $this->getJson(route('api.v1.customers.unpaid'));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['season_label'],
                ],
                'meta' => [
                    'records',
                    'total_due',
                    'current_unpaid_season_label',
                    'unpaid_tabs' => [
                        ['id', 'label', 'is_current', 'count', 'due_total'],
                    ],
                ],
                'errors',
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.records', 2)
            ->assertJsonPath('meta.total_due', 350)
            ->assertJsonPath('errors', []);

        $tabs = $response->json('meta.unpaid_tabs');
        $currentLabel = (string) $response->json('meta.current_unpaid_season_label');

        $this->assertIsArray($tabs);
        $this->assertNotEmpty($tabs);
        $this->assertSame($currentLabel, (string) ($tabs[0]['label'] ?? ''));
        $this->assertTrue((bool) ($tabs[0]['is_current'] ?? false));
    }

    public function test_unpaid_customers_endpoint_supports_search_by_booking_and_customer(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create(['title_bg' => 'Private Lesson']);

        $customer = Customer::factory()->create([
            'firstname' => 'Samuil',
            'lastname' => 'Arnaudov',
        ]);
        $otherCustomer = Customer::factory()->create([
            'firstname' => 'Other',
            'lastname' => 'Child',
        ]);

        $matchingBooking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 250,
            'paid' => 50,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        Booking::query()->create([
            'customer_id' => $otherCustomer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 250,
            'paid' => 100,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        $byName = $this->getJson(route('api.v1.customers.unpaid', ['q' => 'Samuil']));
        $byName
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_id', $customer->id)
            ->assertJsonPath('data.0.booking_id', $matchingBooking->id);

        $byBookingId = $this->getJson(route('api.v1.customers.unpaid', ['q' => (string) $matchingBooking->id]));
        $byBookingId
            ->assertOk();

        /** @var list<array<string,mixed>> $rows */
        $rows = (array) $byBookingId->json('data');

        $matchingRows = collect($rows)
            ->where('booking_id', $matchingBooking->id)
            ->values();

        $this->assertNotEmpty($matchingRows);
        $firstMatchingRow = $matchingRows->first();

        $this->assertIsArray($firstMatchingRow);
        $this->assertSame('Samuil Arnaudov', (string) ($firstMatchingRow['customer_name'] ?? ''));
    }

    public function test_unpaid_customers_endpoint_returns_non_current_season_tab_when_booking_dates_are_in_past_season(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create(['title_bg' => 'Group Lesson']);
        $currentCustomer = Customer::factory()->create(['firstname' => 'Current', 'lastname' => 'Kid']);
        $pastCustomer = Customer::factory()->create(['firstname' => 'Past', 'lastname' => 'Kid']);

        $currentBooking = Booking::query()->create([
            'customer_id' => $currentCustomer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 200,
            'paid' => 50,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        $pastBooking = Booking::query()->create([
            'customer_id' => $pastCustomer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 200,
            'paid' => 20,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        $currentSlot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'name' => 'Current Season Slot',
            'date_start' => '2025-09-01',
            'date_end' => '2026-08-31',
        ]);
        $pastSlot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'name' => '2024/2025',
            'date_start' => '2024-09-01',
            'date_end' => '2025-08-31',
        ]);

        $currentDate = ServiceDate::factory()->create([
            'slot_id' => $currentSlot->id,
            'slot_date' => '2026-01-15',
        ]);
        $pastDate = ServiceDate::factory()->create([
            'slot_id' => $pastSlot->id,
            'slot_date' => '2024-12-15',
        ]);

        BookingDate::query()->create([
            'booking_id' => $currentBooking->id,
            'service_date_id' => $currentDate->id,
        ]);
        BookingDate::query()->create([
            'booking_id' => $pastBooking->id,
            'service_date_id' => $pastDate->id,
        ]);

        $response = $this->getJson(route('api.v1.customers.unpaid'));

        $response
            ->assertOk()
            ->assertJsonPath('meta.records', 2);

        /** @var list<array<string,mixed>> $tabsPayload */
        $tabsPayload = (array) $response->json('meta.unpaid_tabs');
        $tabs = collect($tabsPayload);
        $pastTab = $tabs->first(static fn (array $tab): bool => ($tab['label'] ?? '') === '2024/2025');

        $this->assertNotNull($pastTab);
        $this->assertSame(1, (int) ($pastTab['count'] ?? 0));

        /** @var list<array<string,mixed>> $rows */
        $rows = (array) $response->json('data');

        $pastRow = collect($rows)
            ->first(static fn (array $row): bool => (int) ($row['booking_id'] ?? 0) === $pastBooking->id);

        $this->assertIsArray($pastRow);
        $this->assertSame('2024/2025', (string) ($pastRow['season_label'] ?? ''));
    }

    public function test_unpaid_overview_keeps_totals_and_season_links_across_batches(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();
        $service = Service::factory()->create();
        $bookings = array_map(fn (int $id): array => [
            'id' => $id,
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 20,
            'paid' => 5,
        ], range(1, 1001));
        foreach (array_chunk($bookings, 100) as $batch) {
            Booking::query()->insert($batch);
        }

        $slot = ServiceSlot::factory()->create(['service_id' => $service->id, 'name' => 'Historical test period']);
        $date = ServiceDate::factory()->create(['slot_id' => $slot->id, 'slot_date' => now()->subYears(3)->toDateString()]);
        BookingDate::query()->create(['booking_id' => 1, 'service_date_id' => $date->id]);

        $this->getJson(route('api.v1.customers.unpaid'))
            ->assertOk()
            ->assertJsonCount(1001, 'data')
            ->assertJsonPath('data.0.booking_id', 1001)
            ->assertJsonPath('data.1000.season_label', 'Historical test period')
            ->assertJsonPath('meta.records', 1001)
            ->assertJsonPath('meta.total_due', 15015);
    }
}
