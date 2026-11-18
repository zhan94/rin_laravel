<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Reports;

use App\DTOs\Reports\ReportFilterData;
use App\Models\Booking;
use App\Repositories\Contracts\Reports\ReportRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(
        private readonly ReportRangeQueryBuilder $rangeQueryBuilder,
        private readonly ReportSkillTitleLookup $skillTitleLookup,
    ) {}

    public function countBookingsForRange(ReportFilterData $filters): int
    {
        return (int) $this->rangeQueryBuilder->make($filters)
            ->distinct('b.id')
            ->count('b.id');
    }

    public function getBookingIdsForRange(ReportFilterData $filters, int $limit, int $offset): array
    {
        $query = $this->rangeQueryBuilder->make($filters)
            ->select('b.id')
            ->groupBy('b.id');

        $this->rangeQueryBuilder->applySort($query, $filters);

        $bookingIds = $query
            ->limit(max(1, $limit))
            ->offset(max(0, $offset))
            ->pluck('b.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();

        /** @var list<int> $bookingIds */
        return $bookingIds;
    }

    public function getBookingsForIds(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $rows = DB::table('bookings as b')
            ->join('booking_dates as bd', 'bd.booking_id', '=', 'b.id')
            ->join('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
            ->leftJoin('booking_winter_details as bwd', 'bwd.booking_id', '=', 'b.id')
            ->leftJoin('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('locations as l', 'l.id', '=', 'b.location')
            ->leftJoin('equips as e', 'e.id', '=', 'bwd.equip_id')
            ->where('b.service_type', Booking::SERVICE_TYPE_SERVICE)
            ->whereIn('b.id', $bookingIds)
            ->orderByDesc('b.id')
            ->orderBy('sd.slot_date')
            ->get([
                'b.id as booking_id',
                'b.customer_id',
                'b.service as service_id',
                'b.location as location_id',
                'b.sum_paid',
                'b.paid',
                'b.note',
                'c.firstname',
                'c.surname',
                'c.lastname',
                'c.date_birth',
                'c.parent_name',
                'c.parent_phone',
                'c.email',
                'l.location_from',
                'l.location_to',
                'l.from_time',
                'l.to_time',
                'bwd.skill_id',
                'bwd.equip_id',
                'bwd.shoe_size',
                'bwd.height',
                'e.title as equip_title',
                'sd.slot_date',
            ]);

        $bookings = $rows
            ->map(static function (object $row): array {
                return [
                    'booking_id' => (int) ($row->booking_id ?? 0),
                    'customer_id' => (int) ($row->customer_id ?? 0),
                    'service_id' => (int) ($row->service_id ?? 0),
                    'location_id' => (int) ($row->location_id ?? 0),
                    'sum_paid' => $row->sum_paid !== null ? (int) $row->sum_paid : null,
                    'paid' => $row->paid !== null ? (int) $row->paid : null,
                    'note' => $row->note !== null ? (string) $row->note : null,
                    'firstname' => $row->firstname !== null ? (string) $row->firstname : null,
                    'surname' => $row->surname !== null ? (string) $row->surname : null,
                    'lastname' => $row->lastname !== null ? (string) $row->lastname : null,
                    'date_birth' => $row->date_birth !== null ? (string) $row->date_birth : null,
                    'parent_name' => $row->parent_name !== null ? (string) $row->parent_name : null,
                    'parent_phone' => $row->parent_phone !== null ? (string) $row->parent_phone : null,
                    'email' => $row->email !== null ? (string) $row->email : null,
                    'location_from' => $row->location_from !== null ? (string) $row->location_from : null,
                    'location_to' => $row->location_to !== null ? (string) $row->location_to : null,
                    'from_time' => $row->from_time !== null ? (string) $row->from_time : null,
                    'to_time' => $row->to_time !== null ? (string) $row->to_time : null,
                    'skill_id' => $row->skill_id !== null ? (int) $row->skill_id : null,
                    'equip_id' => $row->equip_id !== null ? (int) $row->equip_id : null,
                    'shoe_size' => $row->shoe_size !== null ? (int) $row->shoe_size : null,
                    'height' => $row->height !== null ? (int) $row->height : null,
                    'equip_title' => $row->equip_title !== null ? (string) $row->equip_title : null,
                    'slot_date' => $row->slot_date !== null ? (string) $row->slot_date : null,
                ];
            })
            ->values()
            ->all();

        /** @var list<array<string,mixed>> $bookings */
        return $bookings;
    }

    public function getTotalsForRange(ReportFilterData $filters): array
    {
        $subquery = $this->rangeQueryBuilder->make($filters)
            ->selectRaw('DISTINCT b.id');

        $row = DB::table('bookings as b')
            ->whereIn('b.id', $subquery)
            ->selectRaw('COALESCE(SUM(b.sum_paid), 0) AS sum_total')
            ->selectRaw('COALESCE(SUM(b.paid), 0) AS paid_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN (COALESCE(b.sum_paid, 0) - COALESCE(b.paid, 0)) > 0 THEN 1 ELSE 0 END), 0) AS due_count')
            ->first();

        return [
            'sum_total' => (int) ($row->sum_total ?? 0),
            'paid_total' => (int) ($row->paid_total ?? 0),
            'due_count' => (int) ($row->due_count ?? 0),
        ];
    }

    public function mapSkillTitles(array $skillIds): array
    {
        return $this->skillTitleLookup->map($skillIds);
    }
}
