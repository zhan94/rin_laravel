<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\Models\Booking;
use App\Models\Customer;
use App\Support\Localization\DisplayDate;
use App\Support\Locations\LocationLabel;
use App\Support\Names\PersonName;
use DateTimeInterface;

final class BookingSupportPresenter
{
    /**
     * @param  array<string,mixed>  $row
     * @return array<string,mixed>
     */
    public function autocompleteCustomer(array $row): array
    {
        $name = PersonName::fromParts([
            $row['firstname'] ?? null,
            $row['surname'] ?? null,
            $row['lastname'] ?? null,
        ]);

        $birth = DisplayDate::german((string) ($row['date_birth'] ?? ''), '');

        return [
            'value' => $name,
            'label' => $birth !== '' ? ($name.' ('.$birth.')') : $name,
            'customer_id' => (int) ($row['id'] ?? 0),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function customer(Customer $customer): array
    {
        $birth = $customer->getAttribute('date_birth');

        return [
            'id' => (int) $customer->id,
            'firstname' => (string) $customer->firstname,
            'surname' => $customer->surname,
            'lastname' => (string) $customer->lastname,
            'full_name' => $customer->fullName(),
            'date_birth' => $birth instanceof DateTimeInterface ? $birth->format('Y-m-d') : $birth,
            'age' => $customer->age,
            'parent_name' => $customer->parent_name,
            'parent_phone' => $customer->parent_phone,
            'email' => $customer->email,
            'active' => (bool) $customer->active,
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $locations
     * @param  list<string>  $disabledDates
     * @return array<string,mixed>
     */
    public function availability(array $locations, array $disabledDates): array
    {
        $main = [];
        $custom = [];

        foreach ($locations as $location) {
            $mapped = $this->mapLocation($location);

            if ($mapped['is_global']) {
                $main[] = $mapped;

                continue;
            }

            $custom[] = $mapped;
        }

        return [
            'disabled_dates' => $disabledDates,
            'locations' => [
                ...$main,
                ...$custom,
            ],
            'location_groups' => [
                'main' => $main,
                'custom' => $custom,
            ],
        ];
    }

    /**
     * @param  array<string,mixed>|null  $row
     * @return array<string,mixed>
     */
    public function bonusContext(?array $row): array
    {
        if ($row === null) {
            return [
                'found' => false,
                'location' => '',
                'date_from' => null,
                'date_to' => null,
            ];
        }

        return [
            'found' => true,
            'id' => (int) ($row['id'] ?? 0),
            'location' => LocationLabel::route($row['location_from'] ?? null, $row['location_to'] ?? null),
            'date_from' => $row['date_from'] ?? null,
            'date_to' => $row['date_to'] ?? null,
        ];
    }

    /**
     * @param  array{start:string,end:string}  $range
     * @return array<string,mixed>
     */
    public function emptyPrefill(array $range): array
    {
        return [
            'found' => false,
            'booking' => null,
            'season_start' => $range['start'],
            'season_end' => $range['end'],
        ];
    }

    /**
     * @param  array<string,mixed>  $booking
     * @param  array{start:string,end:string}  $range
     * @return array<string,mixed>
     */
    public function prefill(array $booking, array $range, int $serviceId): array
    {
        $skiMeta = SkiNoteMeta::fromNote($booking['note'] ?? null);

        $skillId = (int) ($booking['skill_id'] ?? 0);
        if ($skillId <= 0) {
            $skillId = $skiMeta->skillIdAsInt();
        }

        $shoeSize = $skiMeta->shoeSize;
        $height = $skiMeta->height;

        if (($booking['shoe_size'] ?? null) !== null) {
            $shoeSize = (string) (int) $booking['shoe_size'];
        }

        if (($booking['height'] ?? null) !== null) {
            $height = (string) (int) $booking['height'];
        }

        $equipEnabled = $booking['equip_enabled'] ?? ($skiMeta->equipEnabledState() === 1);
        if ((int) ($booking['equip_id'] ?? 0) > 0) {
            $equipEnabled = true;
        } elseif (
            in_array($serviceId, Booking::SKI_SERVICE_IDS, true)
            && ($booking['equip_enabled'] ?? null) === null
            && ! $equipEnabled
            && $shoeSize === ''
            && $height === ''
            && $skillId > 0
        ) {
            // Legacy notes before equip_enabled stored only skill for own equipment.
            $equipEnabled = true;
        }

        return [
            'found' => true,
            'booking' => [
                'id' => (int) ($booking['id'] ?? 0),
                'location' => (int) ($booking['location'] ?? 0),
                'skill_id' => $skillId > 0 ? $skillId : null,
                'card_id' => (int) ($booking['card_id'] ?? 0) > 0 ? (int) $booking['card_id'] : null,
                'equip_id' => (int) ($booking['equip_id'] ?? 0) > 0 ? (int) $booking['equip_id'] : null,
                'equip_enabled' => $equipEnabled,
                'approved' => (bool) ($booking['approved'] ?? false),
                'lunch' => (bool) ($booking['lunch'] ?? false),
                'sum_paid' => $booking['sum_paid'] !== null ? (float) $booking['sum_paid'] : null,
                'paid' => $booking['paid'] !== null ? (float) $booking['paid'] : null,
                'note' => $skiMeta->note,
                'ski_form' => [
                    'shoe_size' => $shoeSize,
                    'height' => $height,
                    'skill_id' => $skillId > 0 ? (string) $skillId : '',
                    'equip_enabled' => $equipEnabled ? '1' : '0',
                ],
                'last_date' => $booking['last_date'] ?? null,
                'season_start' => $range['start'],
                'season_end' => $range['end'],
            ],
            'season_start' => $range['start'],
            'season_end' => $range['end'],
        ];
    }

    /**
     * @param  array<string,mixed>  $location
     * @return array<string,mixed>
     */
    private function mapLocation(array $location): array
    {
        $from = trim((string) ($location['location_from'] ?? ''));
        $to = trim((string) ($location['location_to'] ?? ''));
        $fromTime = trim((string) ($location['from_time'] ?? ''));
        $toTime = trim((string) ($location['to_time'] ?? ''));

        $serviceId = (int) ($location['service_id'] ?? 0);

        return [
            'id' => (int) ($location['id'] ?? 0),
            'location_from' => $from,
            'location_to' => $to,
            'from_time' => $fromTime,
            'to_time' => $toTime,
            'service_id' => $serviceId,
            'is_global' => $serviceId <= 0,
            'label' => LocationLabel::routeWithTimes($from, $to, $fromTime, $toTime),
        ];
    }
}
