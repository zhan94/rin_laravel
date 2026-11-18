<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Reports\ReportFilterData;
use App\Enums\RoleKey;
use App\Models\User;
use App\Repositories\Contracts\Reports\ReportRepositoryInterface;
use App\Support\Bookings\SkiNoteMeta;
use App\Support\Reports\ReportPresenter;

final readonly class ReportService
{
    public function __construct(
        private ReportRepositoryInterface $repository,
        private ReportPresenter $presenter,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function overview(ReportFilterData $filters, User $user): array
    {
        $isDriver = $this->isDriver($user);

        if (! $filters->hasDateRange()) {
            return $this->emptyPayload($filters, $isDriver);
        }

        $total = $this->repository->countBookingsForRange($filters);
        $lastPage = max(1, (int) ceil($total / max(1, $filters->perPage)));
        $page = min($filters->page, $lastPage);
        $effectiveFilters = $filters->withPage($page);

        $limit = $effectiveFilters->perPage;
        $offset = ($effectiveFilters->page - 1) * $limit;

        $bookingIds = $this->repository->getBookingIdsForRange($effectiveFilters, $limit, $offset);
        $rowsRaw = $this->repository->getBookingsForIds($bookingIds);
        $skillTitleMap = $this->repository->mapSkillTitles($this->collectSkillIds($rowsRaw));

        $rows = $this->presenter->rows($rowsRaw, $bookingIds, $isDriver, $skillTitleMap);

        $totals = $this->repository->getTotalsForRange($effectiveFilters);
        $sumTotal = $totals['sum_total'];
        $paidTotal = $totals['paid_total'];

        return [
            'is_driver' => $isDriver,
            'filters' => [
                'service_id' => $effectiveFilters->serviceId,
                'from' => $effectiveFilters->from,
                'to' => $effectiveFilters->to,
                'q' => $effectiveFilters->search,
                'sort' => $effectiveFilters->sort,
                'dir' => $effectiveFilters->dir,
            ],
            'rows' => $rows,
            'summary' => [
                'sum_total' => $sumTotal,
                'paid_total' => $paidTotal,
                'due_total' => max(0, $sumTotal - $paidTotal),
                'due_count' => $totals['due_count'],
                'total_count' => $total,
            ],
            'pagination' => [
                'total' => $total,
                'per_page' => $effectiveFilters->perPage,
                'current_page' => $effectiveFilters->page,
                'last_page' => $lastPage,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyPayload(ReportFilterData $filters, bool $isDriver): array
    {
        return [
            'is_driver' => $isDriver,
            'filters' => [
                'service_id' => $filters->serviceId,
                'from' => $filters->from,
                'to' => $filters->to,
                'q' => $filters->search,
                'sort' => $filters->sort,
                'dir' => $filters->dir,
            ],
            'rows' => [],
            'summary' => [
                'sum_total' => 0,
                'paid_total' => 0,
                'due_total' => 0,
                'due_count' => 0,
                'total_count' => 0,
            ],
            'pagination' => [
                'total' => 0,
                'per_page' => $filters->perPage,
                'current_page' => $filters->page,
                'last_page' => 1,
            ],
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $rowsRaw
     * @return list<int>
     */
    private function collectSkillIds(array $rowsRaw): array
    {
        $ids = [];

        foreach ($rowsRaw as $row) {
            $skillId = (int) ($row['skill_id'] ?? 0);
            if ($skillId > 0) {
                $ids[] = $skillId;

                continue;
            }

            $fallbackSkillId = SkiNoteMeta::fromNote($row['note'] ?? null)->skillIdAsInt();
            if ($fallbackSkillId > 0) {
                $ids[] = $fallbackSkillId;
            }
        }

        return array_values(array_unique($ids));
    }

    private function isDriver(User $user): bool
    {
        return $user->resolvedRoleKey() === RoleKey::DRIVER;
    }
}
