<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Support\Bookings\SkiNoteMeta;
use App\Support\Locations\LocationLabel;
use App\Support\Names\PersonName;

final class ReportPresenter
{
    /**
     * @param  list<array<string,mixed>>  $rowsRaw
     * @param  list<int>  $bookingIds
     * @param  array<int,string>  $skillTitleMap
     * @return list<array<string,mixed>>
     */
    public function rows(array $rowsRaw, array $bookingIds, bool $isDriver, array $skillTitleMap): array
    {
        $byBooking = [];

        foreach ($rowsRaw as $row) {
            $bookingId = (int) ($row['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                continue;
            }

            if (! isset($byBooking[$bookingId])) {
                $sumPaid = (int) ($row['sum_paid'] ?? 0);
                $paid = (int) ($row['paid'] ?? 0);

                $noteMeta = SkiNoteMeta::fromNote($row['note'] ?? null);

                $skillId = (int) ($row['skill_id'] ?? 0);
                if ($skillId <= 0) {
                    $skillId = $noteMeta->skillIdAsInt();
                }

                $equipId = (int) ($row['equip_id'] ?? 0);
                $equipTitle = trim((string) ($row['equip_title'] ?? ''));
                if ($equipTitle === '') {
                    $equipTitle = $this->buildEquipLabelFromSkiMeta(
                        skiMeta: $noteMeta,
                        skillId: $skillId,
                        shoeSizeOverride: $row['shoe_size'] ?? null,
                        heightOverride: $row['height'] ?? null,
                    );
                }

                $byBooking[$bookingId] = [
                    'booking_id' => $bookingId,
                    'customer_id' => (int) ($row['customer_id'] ?? 0),
                    'customer_name' => $this->buildCustomerName($row),
                    'birth' => $row['date_birth'] !== null ? (string) $row['date_birth'] : null,
                    'parent_name' => $row['parent_name'] !== null ? (string) $row['parent_name'] : null,
                    'parent_phone' => $row['parent_phone'] !== null ? (string) $row['parent_phone'] : null,
                    'email' => $row['email'] !== null ? (string) $row['email'] : null,
                    'location' => LocationLabel::routeWithTimes(
                        $row['location_from'] ?? null,
                        $row['location_to'] ?? null,
                        $row['from_time'] ?? null,
                        $row['to_time'] ?? null,
                        fallback: 'Unknown',
                    ),
                    'dates' => [],
                    'sum_paid' => $sumPaid,
                    'paid' => $paid,
                    'due' => max(0, $sumPaid - $paid),
                    'note' => $noteMeta->note,
                    'skill' => $isDriver
                        ? null
                        : ($skillId > 0 ? [
                            'id' => $skillId,
                            'title' => $skillTitleMap[$skillId] ?? ('Skill #'.$skillId),
                        ] : null),
                    'equip' => $isDriver
                        ? null
                        : ($equipTitle !== '' ? [
                            'id' => $equipId > 0 ? $equipId : null,
                            'title' => $equipTitle,
                        ] : null),
                ];
            }

            $slotDate = trim((string) ($row['slot_date'] ?? ''));
            if ($slotDate !== '' && ! in_array($slotDate, $byBooking[$bookingId]['dates'], true)) {
                $byBooking[$bookingId]['dates'][] = $slotDate;
            }
        }

        $result = [];
        foreach ($bookingIds as $bookingId) {
            if (! isset($byBooking[$bookingId])) {
                continue;
            }

            $row = $byBooking[$bookingId];
            sort($row['dates']);
            $result[] = $row;
        }

        return $result;
    }

    /**
     * @param  array<string,mixed>  $row
     */
    private function buildCustomerName(array $row): string
    {
        return PersonName::fromParts([
            $row['firstname'] ?? null,
            $row['surname'] ?? null,
            $row['lastname'] ?? null,
        ], '—');
    }

    private function buildEquipLabelFromSkiMeta(
        SkiNoteMeta $skiMeta,
        int $skillId,
        mixed $shoeSizeOverride,
        mixed $heightOverride,
    ): string {
        $shoeSize = trim((string) ($shoeSizeOverride ?? $skiMeta->shoeSize));
        $height = trim((string) ($heightOverride ?? $skiMeta->height));
        $equipEnabled = $skiMeta->equipEnabledState();

        if ($equipEnabled === 1) {
            return 'Own equipment';
        }

        if ($equipEnabled < 0 && $shoeSize === '' && $height === '' && $skillId > 0) {
            return 'Own equipment';
        }

        if ($shoeSize !== '' && $height !== '') {
            return 'Shoe '.$shoeSize.', Height '.$height;
        }

        if ($shoeSize !== '') {
            return 'Shoe '.$shoeSize;
        }

        if ($height !== '') {
            return 'Height '.$height;
        }

        return '';
    }
}
