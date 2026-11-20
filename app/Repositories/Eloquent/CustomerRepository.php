<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Customers\CustomerData;
use App\DTOs\Customers\CustomerFilterData;
use App\Models\Booking;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Support\Localization\LocalizedText;
use App\Support\Names\PersonName;
use App\Support\Pricing\EurCurrency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function paginate(CustomerFilterData $filters): LengthAwarePaginator
    {
        return Customer::query()
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('firstname', 'like', $q)
                        ->orWhere('surname', 'like', $q)
                        ->orWhere('lastname', 'like', $q)
                        ->orWhere('email', 'like', $q)
                        ->orWhere('parent_phone', 'like', $q);

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    /**
     * @return list<array{
     *     customer_id:int,
     *     customer_name:string,
     *     date_birth:?string,
     *     booking_id:int,
     *     service_id:?int,
     *     service_type:?int,
     *     service_title:string,
     *     sum_paid:float,
     *     paid:float,
     *     due:float
     * }>
     */
    public function unpaidRows(?string $search = null): array
    {
        $query = DB::table('bookings as b')
            ->join('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('services as s', function ($join): void {
                $join->on('s.id', '=', 'b.service')
                    ->where('b.service_type', '=', Booking::SERVICE_TYPE_SERVICE);
            })
            ->leftJoin('bonus as bo', function ($join): void {
                $join->on('bo.id', '=', 'b.service')
                    ->where('b.service_type', '=', Booking::SERVICE_TYPE_BONUS);
            })
            ->whereNotNull('b.sum_paid')
            ->whereNotNull('b.paid')
            ->whereColumn('b.sum_paid', '<>', 'b.paid');

        if ($search !== null) {
            $q = '%'.$search.'%';
            $searchId = ctype_digit($search) ? (int) $search : null;

            $query->where(function ($nested) use ($q, $searchId): void {
                $nested->where('c.firstname', 'like', $q)
                    ->orWhere('c.surname', 'like', $q)
                    ->orWhere('c.lastname', 'like', $q)
                    ->orWhere('c.email', 'like', $q)
                    ->orWhere('c.parent_phone', 'like', $q)
                    ->orWhere('s.title', 'like', $q)
                    ->orWhere('bo.title', 'like', $q);

                foreach (LocalizedText::supportedLocales() as $locale) {
                    $nested->orWhere("s.title_translations->{$locale}", 'like', $q)
                        ->orWhere("bo.title_translations->{$locale}", 'like', $q);
                }

                if ($searchId !== null) {
                    $nested->orWhere('c.id', $searchId)
                        ->orWhere('b.id', $searchId);
                }
            });
        }

        $rows = $query
            ->select([
                'c.id as customer_id',
                'c.firstname',
                'c.surname',
                'c.lastname',
                'c.date_birth',
                'b.id as booking_id',
                'b.service as service_id',
                'b.service_type',
                'b.sum_paid',
                'b.paid',
                's.title as service_title',
                's.title_translations as service_title_translations',
                'bo.title as bonus_title',
                'bo.title_translations as bonus_title_translations',
            ])
            ->lazyByIdDesc(1000, 'b.id', 'booking_id');

        $unpaidRows = $rows->map(static function (object $row): array {
            $name = PersonName::fromParts([
                $row->firstname ?? null,
                $row->surname ?? null,
                $row->lastname ?? null,
            ]);

            $sumPaid = (float) ($row->sum_paid ?? 0);
            $paid = (float) ($row->paid ?? 0);
            $serviceType = $row->service_type !== null ? (int) $row->service_type : null;

            $serviceTitle = '';
            if ($serviceType === Booking::SERVICE_TYPE_BONUS) {
                $serviceTitle = LocalizedText::resolve(
                    is_string($row->bonus_title_translations ?? null) ? $row->bonus_title_translations : null,
                    null,
                    (string) ($row->bonus_title ?? ''),
                );
            } else {
                $serviceTitle = LocalizedText::resolve(
                    is_string($row->service_title_translations ?? null) ? $row->service_title_translations : null,
                    null,
                    (string) ($row->service_title ?? ''),
                );
            }
            if ($serviceTitle === '') {
                $serviceId = $row->service_id !== null ? (int) $row->service_id : 0;
                $serviceTitle = $serviceId > 0 ? ('Service #'.$serviceId) : '—';
            }

            return [
                'customer_id' => (int) $row->customer_id,
                'customer_name' => $name !== '' ? $name : ('#'.(int) $row->customer_id),
                'date_birth' => $row->date_birth !== null ? (string) $row->date_birth : null,
                'booking_id' => (int) $row->booking_id,
                'service_id' => $row->service_id !== null ? (int) $row->service_id : null,
                'service_type' => $serviceType,
                'service_title' => $serviceTitle,
                'sum_paid' => $sumPaid,
                'paid' => $paid,
                'due' => EurCurrency::balance($sumPaid, $paid),
            ];
        })->values()->all();

        /** @var list<array{customer_id:int,customer_name:string,date_birth:?string,booking_id:int,service_id:?int,service_type:?int,service_title:string,sum_paid:float,paid:float,due:float}> $unpaidRows */
        return $unpaidRows;
    }

    public function create(CustomerData $data): Customer
    {
        return Customer::query()->create($data->toPersistence());
    }

    public function update(Customer $customer, CustomerData $data): Customer
    {
        $customer->fill($data->toPersistence());
        $customer->save();

        return $customer;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }
}
