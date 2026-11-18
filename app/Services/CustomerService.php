<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Customers\CustomerData;
use App\DTOs\Customers\CustomerFilterData;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use App\Support\Customers\UnpaidOverviewBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class CustomerService
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
        private UnpaidOverviewBuilder $unpaidOverviewBuilder,
    ) {}

    /**
     * @return LengthAwarePaginator<int, \App\Models\Customer>
     */
    public function paginate(CustomerFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
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
     *     sum_paid:int,
     *     paid:int,
     *     due:int
     * }>
     */
    public function unpaidRows(?string $search = null): array
    {
        return $this->repository->unpaidRows($search);
    }

    /**
     * @return array{
     *     rows:list<array{
     *         customer_id:int,
     *         customer_name:string,
     *         date_birth:?string,
     *         booking_id:int,
     *         service_id:?int,
     *         service_type:?int,
     *         service_title:string,
     *         sum_paid:int,
     *         paid:int,
     *         due:int,
     *         season_label:string
     *     }>,
     *     records:int,
     *     total_due:int,
     *     current_unpaid_season_label:string,
     *     unpaid_tabs:list<array{
     *         id:string,
     *         label:string,
     *         is_current:bool,
     *         count:int,
     *         due_total:int
     *     }>
     * }
     */
    public function unpaidOverview(?string $search = null): array
    {
        return $this->unpaidOverviewBuilder->build($this->repository->unpaidRows($search));
    }

    public function create(CustomerData $data, ?User $actor = null): Customer
    {
        return DB::transaction(function () use ($data, $actor): Customer {
            $created = $this->repository->create($data);

            if ($actor instanceof User) {
                $label = $created->displayLabel();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'customers',
                    actionKey: 'created',
                    actionText: 'created',
                    titleEntityLabel: 'Customer',
                    messageEntityLabel: 'customer',
                    label: $label,
                    entityType: 'customer',
                    entityId: (int) $created->id,
                    linkPath: '/crm/customers',
                );
            }

            return $created;
        });
    }

    public function findOrFail(int $id): Customer
    {
        return Customer::query()->findOr(
            $id,
            fn (): never => throw new ApiException(
                errorCode: 'customer_not_found',
                message: 'Customer not found.',
                statusCode: 404,
            ),
        );
    }

    public function update(Customer $customer, CustomerData $data, ?User $actor = null): Customer
    {
        return DB::transaction(function () use ($customer, $data, $actor): Customer {
            $updated = $this->repository->update($customer, $data);

            if ($actor instanceof User) {
                $label = $updated->displayLabel();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'customers',
                    actionKey: 'updated',
                    actionText: 'updated',
                    titleEntityLabel: 'Customer',
                    messageEntityLabel: 'customer',
                    label: $label,
                    entityType: 'customer',
                    entityId: (int) $updated->id,
                    linkPath: '/crm/customers',
                );
            }

            return $updated;
        });
    }

    public function delete(Customer $customer, ?User $actor = null): void
    {
        DB::transaction(function () use ($customer, $actor): void {
            $label = $customer->displayLabel();

            $this->repository->delete($customer);

            if ($actor instanceof User) {
                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'customers',
                    actionKey: 'deleted',
                    actionText: 'deleted',
                    titleEntityLabel: 'Customer',
                    messageEntityLabel: 'customer',
                    label: $label,
                    entityType: 'customer',
                    entityId: (int) $customer->id,
                    linkPath: '/crm/customers',
                );
            }
        });
    }
}
