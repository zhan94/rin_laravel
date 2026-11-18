<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleKey;
use App\Models\BookingDate;
use App\Models\SeasonDefault;
use App\Models\Service;
use App\Models\User;
use App\Support\Seasons\SeasonCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        if (! $user->canAccessRole(RoleKey::GROUP_LEADER)) {
            return to_route('crm.reports.index');
        }

        $today = today();
        $season = SeasonCalendar::infoForDate($today, SeasonCalendar::normalizeDefaults(SeasonDefault::find(1)));
        $bounds = [$season['start']->format('Y-m-d'), $season['end']->format('Y-m-d')];
        $seasonDates = DB::table('service_dates as dates')
            ->where('dates.slot_date', '>=', $bounds[0])
            ->where('dates.slot_date', '<', $season['end']->modify('+1 day')->format('Y-m-d'));

        $seasonBookingIds = (clone $seasonDates)
            ->join('booking_dates as links', 'links.service_date_id', '=', 'dates.id')
            ->select('links.booking_id')->distinct();
        $totals = DB::table('bookings')->joinSub($seasonBookingIds, 'season_bookings', 'season_bookings.booking_id', '=', 'bookings.id')
            ->selectRaw('COUNT(*) AS records, COUNT(DISTINCT customer_id) AS clients, SUM(sum_paid) AS total, SUM(paid) AS paid')
            ->first();

        $customerCounts = DB::table('customers')
            ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN active = ? THEN 1 ELSE 0 END) AS active_count', [true])->first();

        $serviceCounts = (clone $seasonDates)
            ->join('service_slots as slots', 'slots.id', '=', 'dates.slot_id')
            ->leftJoin('booking_dates as links', 'links.service_date_id', '=', 'dates.id')
            ->leftJoin('bookings', 'bookings.id', '=', 'links.booking_id')
            ->groupBy('slots.service_id')
            ->selectRaw('slots.service_id, COUNT(DISTINCT dates.id) AS dates, COUNT(DISTINCT bookings.id) AS records, COUNT(DISTINCT bookings.customer_id) AS clients')
            ->orderByDesc('records')->orderBy('slots.service_id')->get();
        $services = Service::findMany($serviceCounts->pluck('service_id'))->keyBy('id');

        $daily = BookingDate::query()
            ->whereHas('serviceDate', fn ($date) => $date
                ->where('slot_date', '>=', $today->toDateString())
                ->where('slot_date', '<', $today->copy()->addDay()->toDateString()));
        $dailyCount = (clone $daily)->count();
        $dailyRows = $daily->with(['booking.customer', 'booking.locationRecord', 'serviceDate.slot.service'])
            ->orderBy('id')->limit(100)->get();

        return Inertia::render('Dashboard', [
            'season' => ['label' => $season['label'], 'start' => $bounds[0], 'end' => $bounds[1]],
            'today' => $today->toDateString(),
            'canCreate' => $user->canAccessRole(RoleKey::MANAGER),
            'customers' => ['total' => (int) ($customerCounts->total ?? 0), 'active' => (int) ($customerCounts->active_count ?? 0)],
            'stats' => [
                'records' => (int) ($totals->records ?? 0),
                'clients' => (int) ($totals->clients ?? 0),
                'dates' => (clone $seasonDates)->where('dates.is_active', true)->count(),
                'sum' => (int) ($totals->total ?? 0),
                'paid' => (int) ($totals->paid ?? 0),
                'due' => max(0, (int) ($totals->total ?? 0) - (int) ($totals->paid ?? 0)),
                'today' => $dailyCount,
            ],
            'byService' => $serviceCounts->map(fn (object $row): array => [
                'id' => (int) $row->service_id,
                'title' => $services->get($row->service_id)?->displayTitleOrId() ?? '—',
                'dates' => (int) $row->dates,
                'records' => (int) $row->records,
                'clients' => (int) $row->clients,
            ]),
            'daily' => $dailyRows->map(function (BookingDate $row): array {
                $booking = $row->booking;

                return [
                    'id' => $row->id,
                    'customer' => $booking?->customer?->fullName(),
                    'service' => $row->serviceDate?->slot?->service?->displayTitleOrId(),
                    'location' => $booking?->locationRecord?->displayLabel(),
                    'contact' => $booking?->customer?->parent_phone,
                ];
            }),
        ]);
    }
}
