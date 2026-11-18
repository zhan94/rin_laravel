<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Bookings;

use App\Models\Bonus;
use App\Models\Card;
use App\Models\Customer;
use App\Models\Equip;
use App\Models\Location;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Repositories\Contracts\Bookings\BookingSupportRepositoryInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class BookingSupportRepository implements BookingSupportRepositoryInterface
{
    public function searchCustomers(string $query, int $limit = 15): array
    {
        $terms = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($terms === []) {
            return [];
        }

        $limit = max(1, min($limit, 50));

        $rows = Customer::query()
            ->when(count($terms) >= 2, function ($builder) use ($terms): void {
                $builder->where('firstname', 'like', '%'.$terms[0].'%')
                    ->where('lastname', 'like', '%'.$terms[1].'%');
            }, function ($builder) use ($terms): void {
                $term = '%'.$terms[0].'%';
                $builder->where(function ($nested) use ($term): void {
                    $nested->where('firstname', 'like', $term)
                        ->orWhere('surname', 'like', $term)
                        ->orWhere('lastname', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('parent_phone', 'like', $term);
                });
            })
            ->orderBy('firstname')
            ->orderBy('lastname')
            ->limit($limit)
            ->get(['id', 'firstname', 'surname', 'lastname', 'date_birth', 'email', 'parent_phone']);

        $customers = $rows
            ->map(static function (Customer $customer): array {
                return [
                    'id' => (int) $customer->id,
                    'firstname' => (string) $customer->firstname,
                    'surname' => $customer->surname,
                    'lastname' => (string) $customer->lastname,
                    'date_birth' => $customer->date_birth,
                    'email' => $customer->email,
                    'parent_phone' => $customer->parent_phone,
                ];
            })
            ->values()
            ->all();

        /** @var list<array<string,mixed>> $customers */
        return $customers;
    }

    public function findCustomer(int $customerId): ?Customer
    {
        return Customer::query()->find($customerId);
    }

    public function listCardsByService(int $serviceId): array
    {
        $cards = Card::query()
            ->where('service', $serviceId)
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title', 'title_translations'])
            ->map(static function (Card $card): array {
                $translations = $card->displayTranslations();

                return [
                    'id' => (int) $card->id,
                    'title' => $card->displayTitle(),
                    'translations' => $translations,
                ];
            })
            ->values()
            ->all();

        /** @var list<array<string,mixed>> $cards */
        return $cards;
    }

    public function listEquipsByService(int $serviceId): array
    {
        $equips = Equip::query()
            ->where('service', $serviceId)
            ->orderBy('title')
            ->orderBy('id')
            ->get(['id', 'title', 'title_translations'])
            ->map(static function (Equip $equip): array {
                $translations = $equip->displayTranslations();

                return [
                    'id' => (int) $equip->id,
                    'title' => $equip->displayTitle(),
                    'translations' => $translations,
                ];
            })
            ->values()
            ->all();

        /** @var list<array<string,mixed>> $equips */
        return $equips;
    }

    public function listServiceSlots(int $serviceId): array
    {
        $rows = ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->get(['id', 'service_id', 'name', 'date_start', 'date_end', 'max']);
        $slots = [];

        foreach ($rows as $slot) {
            $slots[] = [
                'id' => (int) $slot->id,
                'service_id' => (int) $slot->service_id,
                'name' => (string) $slot->name,
                'date_start' => $this->normalizeDateValue($slot->date_start),
                'date_end' => $this->normalizeDateValue($slot->date_end),
                'max' => (int) $slot->max,
            ];
        }

        /** @var list<array{id:int,service_id:int,name:string,date_start:string|null,date_end:string|null,max:int}> $slots */
        return $slots;
    }

    public function listServiceDates(int $serviceId, int $slotId = 0): array
    {
        $rows = ServiceDate::query()
            ->join('service_slots', 'service_slots.id', '=', 'service_dates.slot_id')
            ->where('service_slots.service_id', $serviceId)
            ->where('service_dates.is_active', true)
            ->when($slotId > 0, fn ($builder) => $builder->where('service_dates.slot_id', $slotId))
            ->orderBy('service_dates.slot_date')
            ->get([
                'service_dates.id',
                'service_dates.slot_id',
                'service_dates.slot_date',
            ]);

        $dates = $rows
            ->map(function (ServiceDate $row): array {
                return [
                    'id' => (int) $row->id,
                    'slot_id' => (int) $row->slot_id,
                    'slot_date' => $this->normalizeDateValue($row->slot_date),
                ];
            })
            ->values()
            ->all();

        /** @var list<array<string,mixed>> $dates */
        return $dates;
    }

    public function listLocationsByService(int $serviceId): array
    {
        $locations = Location::query()
            ->where(function ($query) use ($serviceId): void {
                $query->where('service_id', 0)
                    ->orWhere('service_id', $serviceId);
            })
            ->orderByRaw('CASE WHEN service_id = 0 THEN 0 ELSE 1 END')
            ->orderBy('location_from')
            ->orderBy('location_to')
            ->orderBy('id')
            ->get(['id', 'location_from', 'location_to', 'from_time', 'to_time', 'service_id'])
            ->map(static fn (Location $location): array => [
                'id' => (int) $location->id,
                'location_from' => (string) $location->location_from,
                'location_to' => (string) $location->location_to,
                'from_time' => (string) $location->from_time,
                'to_time' => (string) $location->to_time,
                'service_id' => (int) $location->service_id,
            ])
            ->values()
            ->all();

        /** @var list<array<string,mixed>> $locations */
        return $locations;
    }

    public function listCustomerBookedDates(int $serviceId, int $customerId, int $slotId = 0): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $dates = DB::table('service_dates as sd')
            ->join('service_slots as ss', 'ss.id', '=', 'sd.slot_id')
            ->join('booking_dates as bd', 'bd.service_date_id', '=', 'sd.id')
            ->join('bookings as b', 'b.id', '=', 'bd.booking_id')
            ->where('ss.service_id', $serviceId)
            ->where('b.customer_id', $customerId)
            ->where('b.service', $serviceId)
            ->where('b.service_type', 1)
            ->where('sd.is_active', 1)
            ->when($slotId > 0, fn ($builder) => $builder->where('sd.slot_id', $slotId))
            ->selectRaw('DISTINCT DATE(sd.slot_date) AS slot_date')
            ->orderBy('slot_date')
            ->pluck('slot_date')
            ->filter(static fn ($value): bool => $value !== null && $value !== '')
            ->map(static fn ($value): string => (string) $value)
            ->values()
            ->all();

        /** @var list<string> $dates */
        return $dates;
    }

    public function findBonusContext(int $bonusId): ?array
    {
        $bonus = Bonus::query()
            ->find($bonusId, ['id', 'location_from', 'location_to', 'date_from', 'date_to']);

        if ($bonus === null) {
            return null;
        }

        return [
            'id' => (int) $bonus->id,
            'location_from' => trim((string) $bonus->location_from),
            'location_to' => trim((string) $bonus->location_to),
            'date_from' => $this->normalizeDateValue($bonus->date_from),
            'date_to' => $this->normalizeDateValue($bonus->date_to),
        ];
    }

    public function findCustomerServicePrefill(
        int $customerId,
        int $serviceId,
        string $seasonStart,
        string $seasonEnd,
    ): ?array {
        $subquery = '(SELECT MAX(sd2.slot_date)
            FROM booking_dates bd2
            INNER JOIN service_dates sd2 ON sd2.id = bd2.service_date_id
            WHERE bd2.booking_id = b.id)';

        $row = DB::table('bookings as b')
            ->leftJoin('booking_winter_details as bwd', 'bwd.booking_id', '=', 'b.id')
            ->where('b.customer_id', $customerId)
            ->where('b.service', $serviceId)
            ->where('b.service_type', 1)
            ->whereExists(function ($query) use ($seasonStart, $seasonEnd): void {
                $query
                    ->selectRaw('1')
                    ->from('booking_dates as bd')
                    ->join('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
                    ->whereColumn('bd.booking_id', 'b.id')
                    ->whereDate('sd.slot_date', '>=', $seasonStart)
                    ->whereDate('sd.slot_date', '<=', $seasonEnd);
            })
            ->select([
                'b.id',
                'b.location',
                'b.approved',
                'b.lunch',
                'b.sum_paid',
                'b.paid',
                'b.note',
                'bwd.skill_id',
                'bwd.card_id',
                'bwd.equip_id',
                'bwd.equip_enabled',
                'bwd.shoe_size',
                'bwd.height',
            ])
            ->selectRaw($subquery.' AS last_date')
            ->orderByRaw($subquery.' DESC')
            ->orderByDesc('b.id')
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) ($row->id ?? 0),
            'location' => (int) ($row->location ?? 0),
            'skill_id' => $row->skill_id !== null ? (int) $row->skill_id : null,
            'card_id' => $row->card_id !== null ? (int) $row->card_id : null,
            'equip_id' => $row->equip_id !== null ? (int) $row->equip_id : null,
            'equip_enabled' => $row->equip_enabled !== null ? (bool) $row->equip_enabled : null,
            'shoe_size' => $row->shoe_size !== null ? (int) $row->shoe_size : null,
            'height' => $row->height !== null ? (int) $row->height : null,
            'approved' => (bool) ($row->approved ?? false),
            'lunch' => (bool) ($row->lunch ?? false),
            'sum_paid' => $row->sum_paid !== null ? (int) $row->sum_paid : null,
            'paid' => $row->paid !== null ? (int) $row->paid : null,
            'note' => $row->note !== null ? (string) $row->note : null,
            'last_date' => $row->last_date !== null ? (string) $row->last_date : null,
        ];
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1
                ? substr($value, 0, 10)
                : $value;
        }

        return null;
    }
}
