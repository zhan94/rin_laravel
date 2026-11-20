<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Customers\CustomerData;
use App\DTOs\Customers\CustomerFilterData;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Customer>
     */
    public function paginate(CustomerFilterData $filters): LengthAwarePaginator;

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
    public function unpaidRows(?string $search = null): array;

    public function create(CustomerData $data): Customer;

    public function update(Customer $customer, CustomerData $data): Customer;

    public function delete(Customer $customer): void;
}
