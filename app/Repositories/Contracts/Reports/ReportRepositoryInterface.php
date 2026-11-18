<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Reports;

use App\DTOs\Reports\ReportFilterData;

interface ReportRepositoryInterface
{
    public function countBookingsForRange(ReportFilterData $filters): int;

    /**
     * @return list<int>
     */
    public function getBookingIdsForRange(ReportFilterData $filters, int $limit, int $offset): array;

    /**
     * @param  list<int>  $bookingIds
     * @return list<array<string,mixed>>
     */
    public function getBookingsForIds(array $bookingIds): array;

    /**
     * @return array{sum_total:int,paid_total:int,due_count:int}
     */
    public function getTotalsForRange(ReportFilterData $filters): array;

    /**
     * @param  list<int>  $skillIds
     * @return array<int,string>
     */
    public function mapSkillTitles(array $skillIds): array;
}
