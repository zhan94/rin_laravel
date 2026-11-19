<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Services\ServiceSeasonResource;
use App\Http\Resources\Concerns\FormatsResourceValues;
use App\Models\BookingDate;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Support\Localization\LocalizedText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceScheduleController extends Controller
{
    use FormatsResourceValues;
    use InteractsWithApiResponses;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:active,upcoming,expired,unscheduled'],
            'sort' => ['nullable', 'in:service,season,date,status'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $today = today()->toDateString();
        $status = <<<'SQL'
            CASE
                WHEN service_slots.date_start IS NULL OR service_slots.date_end IS NULL THEN 3
                WHEN DATE(service_slots.date_end) < ? THEN 2
                WHEN DATE(service_slots.date_start) > ? THEN 1
                ELSE 0
            END
            SQL;
        $query = ServiceSlot::query()
            ->select('service_slots.*')
            ->selectRaw("{$status} AS status_rank", [$today, $today])
            ->join('services', 'services.id', '=', 'service_slots.service_id')
            ->with('service.prices')
            ->withCount('dates');

        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $query->where(function (Builder $query) use ($pattern): void {
                $query->where('service_slots.name', 'like', $pattern)
                    ->orWhere('services.title', 'like', $pattern);

                foreach (LocalizedText::supportedLocales() as $locale) {
                    $query->orWhere("services.title_translations->{$locale}", 'like', $pattern);
                }
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_slots.service_id', $request->integer('service_id'));
        }

        if ($request->filled('date')) {
            $date = $request->string('date')->toString();
            $query->whereDate('service_slots.date_start', '<=', $date)
                ->whereDate('service_slots.date_end', '>=', $date);
        }

        if ($request->filled('status')) {
            $rank = array_search($request->string('status')->toString(), ServiceSeasonResource::STATUSES, true);
            $query->whereRaw("({$status}) = ?", [$today, $today, $rank]);
        }

        $direction = $request->string('direction', 'asc')->toString();
        match ($request->string('sort', 'service')->toString()) {
            'season' => $query->orderBy('service_slots.name', $direction),
            'date' => $query->orderBy('service_slots.date_start', $direction),
            'status' => $query->orderBy('status_rank', $direction),
            default => $this->sortByService($query, $request, $direction),
        };

        $page = $query->orderBy('service_slots.date_start')->orderBy('service_slots.id')->paginate(20);

        return $this->success(
            data: ServiceSeasonResource::collection($page->items())->resolve($request),
            meta: [
                'pagination' => $this->pagination($page),
                'counts' => [
                    'services' => Service::query()->count(),
                    'seasons' => ServiceSlot::query()->count(),
                    'dates' => ServiceDate::query()->count(),
                ],
            ],
        );
    }

    public function dates(Request $request, ServiceSlot $slot): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $slot->dates()->withCount(['bookingDates', 'activeBookingDates']);
        if ($request->filled('date')) {
            $query->whereDate('slot_date', $request->string('date')->toString());
        }

        $page = $query->paginate(20);

        return $this->success(
            data: $page->getCollection()->map(function (ServiceDate $date) use ($slot): array {
                $booked = (int) $date->active_booking_dates_count;

                return [
                    'id' => $date->id,
                    'date' => $this->formatDate($date->slot_date),
                    'active' => $date->is_active,
                    'booked' => $booked,
                    'remaining' => max(0, (int) $slot->max - $booked),
                    'bookings_count' => (int) $date->booking_dates_count,
                ];
            })->all(),
            meta: ['pagination' => $this->pagination($page), 'capacity' => (int) $slot->max],
        );
    }

    public function bookings(Request $request, ServiceSlot $slot, int $date): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $day = $slot->dates()->findOrFail($date);
        $page = $day->bookingDates()
            ->with(['booking:id,customer_id,active', 'booking.customer:id,firstname,surname,lastname'])
            ->orderBy('booking_id')
            ->paginate(20);

        return $this->success(
            data: $page->getCollection()->map(static fn (BookingDate $link): array => [
                'id' => $link->booking_id,
                'customer' => $link->booking?->customer?->fullName(),
                'status' => $link->booking?->active ? 'active' : 'cancelled',
            ])->all(),
            meta: ['pagination' => $this->pagination($page)],
        );
    }

    /** @param Builder<ServiceSlot> $query */
    private function sortByService(Builder $query, Request $request, string $direction): void
    {
        $locales = array_unique([
            LocalizedText::currentLocale($request->getLocale()),
            LocalizedText::fallbackLocale(),
            ...LocalizedText::supportedLocales(),
        ]);
        $grammar = $query->getQuery()->getGrammar();
        $titles = array_map(
            static fn (string $locale): string => 'NULLIF(TRIM('.$grammar->wrap("services.title_translations->{$locale}")."), '')",
            $locales,
        );
        $titles[] = 'services.title';

        $query->orderByRaw('COALESCE('.implode(', ', $titles).') '.$direction);
    }

    /**
     * @template TValue
     *
     * @param  LengthAwarePaginator<int,TValue>  $page
     * @return array{total:int,per_page:int,current_page:int,last_page:int}
     */
    private function pagination(LengthAwarePaginator $page): array
    {
        return [
            'total' => $page->total(),
            'per_page' => $page->perPage(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
        ];
    }
}
