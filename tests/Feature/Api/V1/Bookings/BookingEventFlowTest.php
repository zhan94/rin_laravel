<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Bookings;

use App\Enums\RoleKey;
use App\Jobs\SendBookingConfirmation;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingEventFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_creation_dispatches_confirmation_job(): void
    {
        Queue::fake();
        $this->actingAs($this->makeAuthenticatedUser());

        $service = Service::factory()->create(['max' => 2]);
        $location = Location::factory()->create(['service_id' => 0]);

        $response = $this->postJson('/api/v1/bookings', [
            'firstname' => 'Mira',
            'lastname' => 'Petrova',
            'email' => 'mira@example.test',
            'service_type' => 1,
            'service_id' => $service->id,
            'location_id' => $location->id,
            'dates' => ['2026-12-02'],
            'sum_paid' => 100,
            'paid' => 50,
            'skill_id' => 2,
        ]);

        $response->assertCreated();
        $bookingId = (int) $response->json('data.id');

        Queue::assertPushed(
            SendBookingConfirmation::class,
            fn (SendBookingConfirmation $job): bool => $job->bookingId === $bookingId,
        );
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
