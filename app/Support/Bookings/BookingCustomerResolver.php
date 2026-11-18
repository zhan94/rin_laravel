<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Customers\CustomerData;
use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Models\Customer;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use DateTimeImmutable;

final readonly class BookingCustomerResolver
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
    ) {}

    public function resolveCustomerId(?Booking $existing, BookingData $data): int
    {
        $providedCustomerId = $data->customerId;

        if ($providedCustomerId !== null && $providedCustomerId > 0) {
            $customer = Customer::query()->find($providedCustomerId);
            if ($customer === null) {
                throw new ApiException(
                    errorCode: 'customer_not_found',
                    message: 'Customer not found.',
                    statusCode: 404,
                );
            }

            if ($data->hasCustomerSnapshot()) {
                $this->customerRepository->update($customer, $this->buildCustomerData($data));
            }

            return $providedCustomerId;
        }

        if ($existing !== null && $existing->customer_id !== null) {
            $customer = Customer::query()->find((int) $existing->customer_id);

            if ($customer !== null && $data->hasCustomerSnapshot()) {
                $this->customerRepository->update($customer, $this->buildCustomerData($data));
            }

            return (int) $existing->customer_id;
        }

        if (! $data->hasCustomerSnapshot()) {
            throw new ApiException(
                errorCode: 'booking_customer_required',
                message: 'Customer information is required.',
                statusCode: 422,
            );
        }

        $customer = $this->customerRepository->create($this->buildCustomerData($data));

        return (int) $customer->id;
    }

    private function buildCustomerData(BookingData $data): CustomerData
    {
        return new CustomerData(
            firstname: $data->firstname ?? '',
            surname: $data->surname,
            lastname: $data->lastname ?? '',
            dateBirth: $data->dateBirth,
            age: $this->calculateAge($data->dateBirth),
            parentName: $data->parentName,
            parentPhone: $data->parentPhone,
            email: $data->email,
            active: true,
        );
    }

    private function calculateAge(?string $dateBirth): ?int
    {
        if ($dateBirth === null || $dateBirth === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateBirth);
        if (! $date instanceof DateTimeImmutable) {
            return null;
        }

        $today = new DateTimeImmutable('today');
        if ($date > $today) {
            return null;
        }

        return $date->diff($today)->y;
    }
}
