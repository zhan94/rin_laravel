<?php

declare(strict_types=1);

namespace App\DTOs\Bookings;

use App\Models\Booking;
use App\Support\Bookings\SkiNoteMeta;
use App\Support\Pricing\BgnCurrency;
use DateTimeImmutable;

final readonly class BookingData
{
    /**
     * @param  list<string>  $dates
     */
    public function __construct(
        public ?int $customerId,
        public ?string $firstname,
        public ?string $surname,
        public ?string $lastname,
        public ?string $dateBirth,
        public ?string $parentName,
        public ?string $parentPhone,
        public ?string $email,
        public int $serviceType,
        public int $serviceId,
        public int $slotId,
        public ?int $locationId,
        public array $dates,
        public ?int $sumPaid,
        public ?int $paid,
        public bool $approved,
        public bool $lunch,
        public ?string $note,
        public ?int $skillId,
        public ?int $equipId,
        public ?int $cardId,
        public ?int $skiShoeSize,
        public ?int $skiHeight,
        public bool $equipEnabled,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $noteMeta = SkiNoteMeta::fromNote($payload['note'] ?? null);

        $hasBonusField = array_key_exists('bonus', $payload)
            && $payload['bonus'] !== '';

        $serviceType = self::normalizeServiceType(
            $payload['service_type']
                ?? ($hasBonusField
                    ? Booking::SERVICE_TYPE_BONUS
                    : Booking::SERVICE_TYPE_SERVICE),
        );

        $serviceIdInput = $payload['service_id']
            ?? ($serviceType === Booking::SERVICE_TYPE_BONUS
                ? ($payload['bonus'] ?? null)
                : ($payload['service'] ?? null));

        $equipInput = $payload['equip_id'] ?? $payload['equip'] ?? null;

        return new self(
            customerId: self::normalizePositiveInt($payload['customer_id'] ?? null),
            firstname: self::nullableString($payload['firstname'] ?? null),
            surname: self::nullableString($payload['surname'] ?? null),
            lastname: self::nullableString($payload['lastname'] ?? null),
            dateBirth: self::nullableString($payload['date_birth'] ?? null),
            parentName: self::nullableString($payload['parent_name'] ?? null),
            parentPhone: self::nullableString($payload['parent_phone'] ?? null),
            email: self::nullableString($payload['email'] ?? null),
            serviceType: $serviceType,
            serviceId: max(0, (int) $serviceIdInput),
            slotId: max(0, (int) ($payload['slot_id'] ?? $payload['slot'] ?? 0)),
            locationId: self::normalizePositiveInt($payload['location_id'] ?? $payload['location'] ?? null),
            dates: self::normalizeDates($payload['dates'] ?? []),
            sumPaid: self::normalizeAmountToBgn($payload['sum_paid'] ?? null),
            paid: self::normalizeAmountToBgn($payload['paid'] ?? null),
            approved: self::normalizeBool($payload['approved'] ?? false),
            lunch: self::normalizeBool($payload['lunch'] ?? false),
            note: $noteMeta->note,
            skillId: self::normalizePositiveInt(
                $payload['skill_id'] ?? $payload['skill'] ?? $noteMeta->skillId,
            ),
            equipId: self::normalizePositiveInt($equipInput),
            cardId: self::normalizePositiveInt($payload['card_id'] ?? $payload['card'] ?? null),
            skiShoeSize: self::normalizePositiveInt($payload['ski_shoe_size'] ?? $noteMeta->shoeSize),
            skiHeight: self::normalizePositiveInt($payload['ski_height'] ?? $noteMeta->height),
            equipEnabled: self::normalizeBool(
                $payload['equip_enabled']
                    ?? $equipInput
                    ?? $noteMeta->equipEnabled,
            ),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toBookingPersistence(int $customerId): array
    {
        return [
            'customer_id' => $customerId,
            'service' => $this->serviceId,
            'service_type' => $this->serviceType,
            'sum_paid' => $this->sumPaid,
            'paid' => $this->paid,
            'location' => $this->serviceType === Booking::SERVICE_TYPE_SERVICE
                ? $this->locationId
                : null,
            'approved' => $this->approved,
            'lunch' => $this->lunch,
            'note' => $this->note,
        ];
    }

    public function hasCustomerSnapshot(): bool
    {
        return $this->firstname !== null && $this->lastname !== null;
    }

    public function isSkiService(): bool
    {
        return $this->serviceType === Booking::SERVICE_TYPE_SERVICE
            && in_array($this->serviceId, Booking::SKI_SERVICE_IDS, true);
    }

    public function isCardService(): bool
    {
        return $this->serviceType === Booking::SERVICE_TYPE_SERVICE
            && in_array($this->serviceId, Booking::CARD_SERVICE_IDS, true);
    }

    public function isEquipService(): bool
    {
        return $this->serviceType === Booking::SERVICE_TYPE_SERVICE
            && in_array($this->serviceId, Booking::EQUIP_SERVICE_IDS, true);
    }

    public function composeSkiMetaNote(?int $resolvedSkillId = null): ?string
    {
        if (! $this->isSkiService()) {
            return $this->note;
        }

        $meta = $this->buildSkiMetaPayload($resolvedSkillId);

        if ($meta === []) {
            return $this->note;
        }

        return SkiNoteMeta::append($this->note, $meta);
    }

    private static function normalizeServiceType(mixed $value): int
    {
        $type = (int) $value;

        return in_array($type, [Booking::SERVICE_TYPE_SERVICE, Booking::SERVICE_TYPE_BONUS], true)
            ? $type
            : Booking::SERVICE_TYPE_SERVICE;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private static function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private static function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value > 0;
        }

        if ($value === null) {
            return false;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private static function normalizeAmountToBgn(mixed $value): ?int
    {
        return BgnCurrency::intFromEurValue($value);
    }

    /**
     * @return list<string>
     */
    private static function normalizeDates(mixed $input): array
    {
        $values = [];

        if (is_string($input)) {
            $values = array_map('trim', explode(',', $input));
        } elseif (is_array($input)) {
            $values = $input;
        }

        $normalized = [];

        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate === '') {
                continue;
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $candidate);
            if (! $date instanceof DateTimeImmutable) {
                continue;
            }

            $normalized[$date->format('Y-m-d')] = true;
        }

        $dates = array_keys($normalized);
        sort($dates);

        return $dates;
    }

    /**
     * @return array<string,string>
     */
    private function buildSkiMetaPayload(?int $resolvedSkillId): array
    {
        $skillId = $resolvedSkillId !== null && $resolvedSkillId > 0
            ? $resolvedSkillId
            : (($this->skillId !== null && $this->skillId > 0) ? $this->skillId : null);

        $shoeSize = ($this->skiShoeSize !== null && $this->skiShoeSize > 0)
            ? (string) $this->skiShoeSize
            : '';

        $height = ($this->skiHeight !== null && $this->skiHeight > 0)
            ? (string) $this->skiHeight
            : '';

        if ($shoeSize === '' && $height === '' && $skillId === null && ! $this->equipEnabled) {
            return [];
        }

        $meta = [
            'shoe_size' => $shoeSize,
            'height' => $height,
            'equip_enabled' => $this->equipEnabled ? '1' : '0',
        ];

        if ($skillId !== null) {
            $meta['skill_id'] = (string) $skillId;
        }

        return $meta;
    }
}
