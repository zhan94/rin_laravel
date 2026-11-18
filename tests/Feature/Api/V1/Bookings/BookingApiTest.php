<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Bookings;

use App\Enums\RoleKey;
use App\Models\Bonus;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_can_be_created_with_auto_slot_and_payment_sync(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $service = Service::factory()->create(['max' => 2]);
        $location = Location::factory()->create(['service_id' => 0]);

        $payload = [
            'firstname' => 'Mira',
            'lastname' => 'Petrova',
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-02', '2026-12-01'],
            'sum_paid' => 100,
            'paid' => 50,
            'skill_id' => 2,
            'equip_enabled' => false,
        ];

        $response = $this->postJson('/api/v1/bookings', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.customer.full_name', 'Mira Petrova')
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.booking_dates.0.slot_date', '2026-12-01')
            ->assertJsonPath('errors', []);

        $bookingId = (int) $response->json('data.id');

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'service' => $service->id,
            'service_type' => 1,
            'location' => $location->id,
            'sum_paid' => 196,
            'paid' => 98,
        ]);

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $bookingId,
            'sum_paid' => 196,
            'paid' => 98,
        ]);

        $this->assertDatabaseCount('booking_dates', 2);
        $this->assertDatabaseCount('service_slots', 1);
        $this->assertDatabaseHas('service_dates', [
            'slot_date' => '2026-12-01 00:00:00',
        ]);
    }

    public function test_creation_retry_keeps_one_booking_and_one_customer(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());
        $bonus = Bonus::factory()->create();
        $payload = [
            'firstname' => 'Synthetic', 'lastname' => 'Retry', 'service_type' => 2,
            'service_id' => $bonus->id, 'request_key' => Str::uuid()->toString(),
        ];
        $first = $this->postJson('/api/v1/bookings', $payload)->assertCreated();
        $this->postJson('/api/v1/bookings', $payload)->assertCreated()->assertJsonPath('data.id', $first->json('data.id'));
        $this->postJson('/api/v1/bookings', array_replace($payload, ['lastname' => 'Other']))->assertConflict();
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_same_customer_cannot_be_booked_twice_for_same_date_and_service(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $service = Service::factory()->create(['max' => 5]);
        $location = Location::factory()->create(['service_id' => 0]);

        $first = $this->postJson('/api/v1/bookings', [
            'firstname' => 'Niki',
            'lastname' => 'Ivanov',
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-05'],
            'skill_id' => 1,
        ])->assertCreated();

        $customerId = (int) $first->json('data.customer_id');

        $second = $this->postJson('/api/v1/bookings', [
            'customer_id' => $customerId,
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-05'],
            'skill_id' => 1,
        ]);

        $second
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'booking_date_conflict');
    }

    public function test_capacity_is_enforced_for_service_date(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $service = Service::factory()->create(['max' => 1]);
        $location = Location::factory()->create(['service_id' => 0]);

        $this->postJson('/api/v1/bookings', [
            'firstname' => 'Ani',
            'lastname' => 'Georgieva',
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-10'],
            'skill_id' => 1,
        ])->assertCreated();

        $second = $this->postJson('/api/v1/bookings', [
            'firstname' => 'Toni',
            'lastname' => 'Stoyanov',
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-10'],
            'skill_id' => 2,
        ]);

        $second
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'booking_capacity_exceeded');
    }

    public function test_bonus_booking_can_be_created_from_legacy_bonus_field_without_dates_and_location(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $bonus = Bonus::factory()->create();

        $response = $this->postJson('/api/v1/bookings', [
            'firstname' => 'Boni',
            'lastname' => 'Traveler',
            'service_type' => 2,
            'bonus' => $bonus->id,
            'sum_paid' => 20,
            'paid' => 10,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.service_type', 2)
            ->assertJsonPath('data.service_id', $bonus->id)
            ->assertJsonPath('data.location_id', null)
            ->assertJsonPath('data.booking_dates', []);

        $bookingId = (int) $response->json('data.id');

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'service_type' => 2,
            'service' => $bonus->id,
            'location' => null,
        ]);
    }

    public function test_bonus_booking_rejects_slot_assignment(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $bonus = Bonus::factory()->create();

        $response = $this->postJson('/api/v1/bookings', [
            'firstname' => 'Iva',
            'lastname' => 'Marinova',
            'service_type' => 2,
            'bonus' => $bonus->id,
            'slot_id' => 7,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'booking_slot_not_allowed');
    }

    public function test_ski_booking_extracts_note_meta_and_accepts_legacy_alias_fields(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $service = Service::factory()->create(['id' => 1, 'max' => 4]);
        $location = Location::factory()->create(['service_id' => 0]);

        $note = "Needs focus\n\n[[SKI_META]]\n{\"shoe_size\":\"41\",\"height\":\"150\",\"skill_id\":\"2\",\"equip_enabled\":\"0\"}\n[[/SKI_META]]";

        $response = $this->postJson('/api/v1/bookings', [
            'firstname' => 'Raya',
            'lastname' => 'Dimitrova',
            'service_type' => 1,
            'service' => $service->id,
            'location' => $location->id,
            'dates' => '2026-12-21, 2026-12-20',
            'skill' => 4,
            'equip' => 9,
            'card' => 6,
            'note' => $note,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.booking_dates.0.slot_date', '2026-12-20')
            ->assertJsonPath('data.winter_detail.skill_id', 4)
            ->assertJsonPath('data.winter_detail.equip_id', 9)
            ->assertJsonPath('data.winter_detail.card_id', 6)
            ->assertJsonPath('data.winter_detail.shoe_size', 41)
            ->assertJsonPath('data.winter_detail.height', 150);

        $bookingId = (int) $response->json('data.id');
        $storedNote = (string) $response->json('data.note');

        $this->assertStringContainsString('Needs focus', $storedNote);
        $this->assertStringContainsString('[[SKI_META]]', $storedNote);
        $this->assertStringContainsString('"skill_id":"4"', $storedNote);
        $this->assertStringContainsString('"equip_enabled":"1"', $storedNote);
        $this->assertSame(1, substr_count($storedNote, '[[SKI_META]]'));

        $this->assertDatabaseHas('booking_winter_details', [
            'booking_id' => $bookingId,
            'skill_id' => 4,
            'equip_id' => 9,
            'card_id' => 6,
            'shoe_size' => 41,
            'height' => 150,
        ]);
    }

    public function test_authenticated_user_can_list_bookings(): void
    {
        $this->actingAs($this->makeAuthenticatedUser());

        $service = Service::factory()->create(['max' => 3]);
        $location = Location::factory()->create(['service_id' => 0]);

        $this->postJson('/api/v1/bookings', [
            'firstname' => 'Lora',
            'lastname' => 'Markova',
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-15'],
            'skill_id' => 1,
        ])->assertCreated();

        $response = $this->getJson('/api/v1/bookings');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('errors', []);
    }

    private function makeAuthenticatedUser(): User
    {
        $role = Role::query()->firstOrCreate(
            ['role_key' => RoleKey::ADMIN],
            RoleCatalog::payload(RoleKey::ADMIN, 1),
        );

        return User::factory()->create([
            'role_id' => (int) $role->id,
        ]);
    }
}
