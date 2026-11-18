<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Notifications;

use App\Enums\RoleKey;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerActionNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_customer_create_notifies_admins_only(): void
    {
        $manager = $this->createUserWithRole(RoleKey::MANAGER);
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $superAdmin = $this->createUserWithRole(RoleKey::SUPER_ADMIN);
        $groupLeader = $this->createUserWithRole(RoleKey::GROUP_LEADER);

        $this->actingAs($manager);

        $response = $this->postJson('/api/v1/customers', [
            'firstname' => 'Mira',
            'lastname' => 'Petrova',
            'email' => 'mira.petrova@example.com',
            'active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('errors', []);

        $customerId = (int) $response->json('data.id');
        $this->assertTrue($customerId > 0);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'actor_user_id' => $manager->id,
            'notification_type' => 'manager.customers.created',
            'module_key' => 'customers',
            'action_key' => 'created',
            'entity_type' => 'customer',
            'entity_id' => $customerId,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superAdmin->id,
            'actor_user_id' => $manager->id,
            'notification_type' => 'manager.customers.created',
            'entity_id' => $customerId,
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $manager->id,
            'entity_type' => 'customer',
            'entity_id' => $customerId,
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $groupLeader->id,
            'entity_type' => 'customer',
            'entity_id' => $customerId,
        ]);
    }

    public function test_non_manager_customer_create_does_not_emit_admin_notification(): void
    {
        $adminActor = $this->createUserWithRole(RoleKey::ADMIN);
        $adminRecipient = $this->createUserWithRole(RoleKey::ADMIN);
        $superAdmin = $this->createUserWithRole(RoleKey::SUPER_ADMIN);

        $this->actingAs($adminActor);

        $response = $this->postJson('/api/v1/customers', [
            'firstname' => 'Ani',
            'lastname' => 'Ivanova',
            'email' => 'ani.ivanova@example.com',
            'active' => true,
        ]);

        $response->assertCreated();

        $customerId = (int) $response->json('data.id');

        $this->assertDatabaseMissing('user_notifications', [
            'entity_type' => 'customer',
            'entity_id' => $customerId,
            'notification_type' => 'manager.customers.created',
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $adminRecipient->id,
            'entity_type' => 'customer',
            'entity_id' => $customerId,
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $superAdmin->id,
            'entity_type' => 'customer',
            'entity_id' => $customerId,
        ]);
    }

    public function test_manager_payment_apply_notifies_admins(): void
    {
        $manager = $this->createUserWithRole(RoleKey::MANAGER);
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $superAdmin = $this->createUserWithRole(RoleKey::SUPER_ADMIN);

        $booking = $this->makeBooking(sumPaidBgn: 196, paidBgn: 0);

        $this->actingAs($manager);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 50,
            'mode' => 'increment',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'actor_user_id' => $manager->id,
            'notification_type' => 'manager.payments.updated',
            'module_key' => 'payments',
            'action_key' => 'updated',
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $superAdmin->id,
            'actor_user_id' => $manager->id,
            'notification_type' => 'manager.payments.updated',
            'entity_id' => $booking->id,
        ]);
    }

    private function createUserWithRole(RoleKey $roleKey): User
    {
        $role = Role::query()->firstOrCreate(
            ['role_key' => $roleKey->value],
            RoleCatalog::payload($roleKey, 1),
        );

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    private function makeBooking(int $sumPaidBgn, int $paidBgn): Booking
    {
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $location = Location::factory()->create(['service_id' => 0]);

        return Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => $sumPaidBgn,
            'paid' => $paidBgn,
            'location' => $location->id,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);
    }
}
