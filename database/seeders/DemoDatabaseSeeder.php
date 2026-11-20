<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Issues\IssueCreateData;
use App\DTOs\Issues\IssueStatusUpdateData;
use App\Enums\IssueStatus;
use App\Enums\RoleKey;
use App\Models\Bonus;
use App\Models\Booking;
use App\Models\Card;
use App\Models\Customer;
use App\Models\Equip;
use App\Models\Log;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use App\Services\IssueService;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SyntheticDatabase;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class DemoDatabaseSeeder extends Seeder
{
    public function run(BookingService $bookings, IssueService $issues): void
    {
        $generatedPassword = null;
        SyntheticDatabase::seed('rin.compose.demo.v1', ['users', 'customers', 'bookings', 'issue_reports', 'logs'], function () use ($bookings, $issues, &$generatedPassword): void {
            $this->call(CoreBusinessSeeder::class);

            if (is_readable('/run/rin/demo-password')) {
                $file = file_get_contents('/run/rin/demo-password');
                $password = $file === false ? '' : rtrim($file);
                if (strlen($password) < 24) {
                    throw new RuntimeException('The Compose demo needs its generated account password.');
                }
            } else {
                $password = $generatedPassword = Str::random(32);
            }
            $hash = Hash::make($password);
            $roles = Role::query()->pluck('id', 'role_key');
            $staff = [];
            foreach (RoleKey::cases() as $role) {
                $staff[$role->value] = User::factory()->create([
                    'username' => 'demo.'.$role->value,
                    'firstname' => 'Demo',
                    'lastname' => ucwords(str_replace('_', ' ', $role->value)),
                    'email' => $role->value.'@example.test',
                    'email_verified_at' => now(),
                    'password' => $hash,
                    'is_active' => true,
                    'role_id' => $roles->get($role->value),
                ]);
            }

            $today = CarbonImmutable::today();
            $customerIds = [];
            for ($id = 1; $id <= 60; $id++) {
                $customerIds[] = Customer::query()->forceCreate([
                    'id' => $id,
                    'firstname' => 'Sample',
                    'lastname' => sprintf('Customer %03d', $id),
                    'date_birth' => $today->subYears(10 + ($id % 6)),
                    'parent_name' => 'Demo Guardian '.$id,
                    'parent_phone' => sprintf('+441632960%03d', $id),
                    'email' => 'customer.'.$id.'@example.test',
                    'active' => true,
                ])->id;
            }

            $sampleDates = [[0, 1], [0], [0, 2], [-2, -1], [-24, -23], [-21], [6, 7], [25, 26]];
            $this->seedDatedBookings($bookings, $today, 1, array_slice($customerIds, 0, 8), $sampleDates);
            $this->seedDatedBookings($bookings, $today, 4, array_slice($customerIds, 8, 8), $sampleDates);
            $this->seedDatedBookings($bookings, $today, 11, [$customerIds[16]], [[2, 3, 4]]);
            $this->seedDatedBookings($bookings, $today, 3, [$customerIds[17]], [[4]]);
            $this->seedDatedBookings($bookings, $today, 5, [$customerIds[18]], [[25]]);
            $this->seedBonusBookings($bookings, array_slice($customerIds, 19, 3));
            $this->seedStaffActivity($issues, $staff['group_leader'], $staff['admin']);
        });

        /** @var Command|null $command */
        $command = $this->command;
        if ($generatedPassword !== null && $command !== null) {
            $command->info('Generated password for the synthetic staff accounts');
            $command->line($generatedPassword);
        }
    }

    /**
     * @param  list<int>  $customerIds
     * @param  list<list<int>>  $schedule
     */
    private function seedDatedBookings(BookingService $bookings, CarbonImmutable $today, int $serviceId, array $customerIds, array $schedule): void
    {
        $service = Service::query()->with('prices')->findOrFail($serviceId);
        $locationId = $service->locations()->orderBy('id')->firstOrFail()->id;
        $ski = in_array($serviceId, Booking::SKI_SERVICE_IDS, true);
        $card = in_array($serviceId, Booking::CARD_SERVICE_IDS, true)
            ? Card::query()->with('prices')->where('service', $serviceId)->orderBy('id')->firstOrFail()
            : null;
        $rental = in_array($serviceId, Booking::EQUIP_SERVICE_IDS, true)
            ? Equip::query()->with('prices')->where('service', $serviceId)->orderBy('id')->firstOrFail()
            : null;

        foreach ($schedule as $index => $offsets) {
            $bookPass = $card !== null && $index % 2 === 0;
            $bookRental = $rental !== null && $index % 3 === 0;
            $days = count($offsets);
            $total = (float) ($service->prices->firstWhere('days', $days)->price
                ?? $days * (float) $service->prices->firstWhere('days', 1)?->price);
            if ($bookPass) {
                $total += $days * (float) $card->prices->firstWhere('days', 1)?->price;
            }
            if ($bookRental) {
                $total += $days * (float) $rental->prices->firstWhere('days', 1)?->price;
            }
            $paid = match ($index) {
                0, 3, 4 => $total,
                1, 5, 7 => $total / 2,
                default => 0,
            };

            $booking = $bookings->create(BookingData::fromArray([
                'customer_id' => $customerIds[$index],
                'service_id' => $service->id,
                'location_id' => $locationId,
                'dates' => array_map(static fn (int $offset): string => $today->addDays($offset)->toDateString(), $offsets),
                'skill_id' => $ski ? 1 + ($index % 3) : null,
                'card_id' => $bookPass ? $card->id : null,
                'equip_id' => $bookRental ? $rental->id : null,
                'equip_enabled' => $bookRental,
                'ski_shoe_size' => $bookRental ? 36 + ($index % 4) : null,
                'ski_height' => $bookRental ? 140 + ($index % 5) : null,
                'sum_paid' => $total,
                'paid' => $paid,
                'approved' => $index !== 2,
            ]));

            if ($index === 5) {
                $bookings->deactivate($booking);
            }
        }
    }

    /** @param list<int> $customerIds */
    private function seedBonusBookings(BookingService $bookings, array $customerIds): void
    {
        foreach (Bonus::query()->orderBy('id')->get()->values() as $index => $bonus) {
            $total = (float) $bonus->price;
            $paid = match ($index) {
                0 => $total,
                1 => $total / 2,
                default => 0,
            };

            $bookings->create(BookingData::fromArray([
                'customer_id' => $customerIds[$index],
                'service_id' => $bonus->id,
                'service_type' => Booking::SERVICE_TYPE_BONUS,
                'sum_paid' => $total,
                'paid' => $paid,
            ]));
        }
    }

    private function seedStaffActivity(IssueService $issues, User $reporter, User $assignee): void
    {
        foreach ([
            ['locations', 'location_mapping_problem', 'Confirm the valley shuttle pickup label', IssueStatus::OPEN],
            ['services', 'date_calendar_problem', 'Review next month’s ski school dates', IssueStatus::IN_PROGRESS],
            ['reports', 'export_or_print_problem', 'Check the transport report’s print view', IssueStatus::RESOLVED],
        ] as [$module, $key, $title, $status]) {
            $issue = $issues->create($reporter, new IssueCreateData(
                $module, $key, 'normal', $title, 'Synthetic handover task for the demonstration.', '/crm/'.$module, $assignee->id, [],
            ));
            if ($status !== IssueStatus::OPEN) {
                $issues->updateStatus($reporter, (int) $issue->id, new IssueStatusUpdateData($status->value, 'Checked against the synthetic schedule.'));
            }

            Log::factory()->create([
                'user' => $reporter->id,
                'page' => '/crm/'.$module,
                'ip' => '192.0.2.10',
                'location' => null,
            ]);
        }
    }
}
