<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Reports;

use App\DTOs\Reports\ReportFilterData;
use App\Models\Booking;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class ReportRangeQueryBuilder
{
    public function make(ReportFilterData $filters): Builder
    {
        $query = DB::table('bookings as b')
            ->join('booking_dates as bd', 'bd.booking_id', '=', 'b.id')
            ->join('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
            ->join('service_slots as ss', 'ss.id', '=', 'sd.slot_id')
            ->join('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('booking_winter_details as bwd', 'bwd.booking_id', '=', 'b.id')
            ->leftJoin('locations as l', 'l.id', '=', 'b.location')
            ->leftJoin('equips as e', 'e.id', '=', 'bwd.equip_id')
            ->where('b.service_type', Booking::SERVICE_TYPE_SERVICE)
            ->whereDate('sd.slot_date', '>=', (string) $filters->from)
            ->whereDate('sd.slot_date', '<=', (string) $filters->to);

        if ($filters->serviceId !== null) {
            $query->where('b.service', $filters->serviceId);
        }

        if ($filters->search !== '') {
            $term = '%'.$filters->search.'%';
            $fullName = $query->getConnection()->getDriverName() === 'sqlite'
                ? "COALESCE(c.firstname, '') || ' ' || COALESCE(c.lastname, '')"
                : "CONCAT(COALESCE(c.firstname, ''), ' ', COALESCE(c.lastname, ''))";

            $query->where(function (Builder $nested) use ($term, $filters, $fullName): void {
                $nested->where('c.firstname', 'like', $term)
                    ->orWhere('c.lastname', 'like', $term)
                    ->orWhere('c.parent_name', 'like', $term)
                    ->orWhere('c.parent_phone', 'like', $term)
                    ->orWhereRaw("($fullName) LIKE ?", [$term]);

                if (! $filters->isDriver) {
                    $nested->orWhere('c.email', 'like', $term);
                }
            });
        }

        return $query;
    }

    public function applySort(Builder $query, ReportFilterData $filters): void
    {
        $direction = $filters->dir === 'asc' ? 'ASC' : 'DESC';

        switch ($filters->sort) {
            case 'name':
                $query->orderByRaw('MIN(c.lastname) '.$direction)
                    ->orderByRaw('MIN(c.firstname) '.$direction);
                break;

            case 'birth':
                $query->orderByRaw('MIN(c.date_birth) '.$direction);
                break;

            case 'level':
                $query->orderByRaw('MIN(COALESCE(bwd.skill_id, 0)) '.$direction);
                break;

            case 'equip':
                $query->orderByRaw("MIN(COALESCE(e.title, '')) ".$direction);
                break;

            case 'location':
                $query->orderByRaw("MIN(COALESCE(l.location_from, '')) ".$direction)
                    ->orderByRaw("MIN(COALESCE(l.location_to, '')) ".$direction);
                break;

            case 'due':
                $query->orderByRaw('MIN(COALESCE(b.sum_paid, 0) - COALESCE(b.paid, 0)) '.$direction);
                break;

            case 'parent':
                $query->orderByRaw("MIN(COALESCE(c.parent_name, '')) ".$direction);
                break;

            case 'id':
            default:
                $query->orderBy('b.id', $filters->dir);
                break;
        }

        $query->orderByDesc('b.id');
    }
}
