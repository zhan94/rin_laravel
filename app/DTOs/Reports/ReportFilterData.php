<?php

declare(strict_types=1);

namespace App\DTOs\Reports;

final readonly class ReportFilterData
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * @var list<string>
     */
    private const SORT_OPTIONS = ['id', 'name', 'birth', 'level', 'equip', 'location', 'due', 'parent'];

    public function __construct(
        public ?int $serviceId,
        public ?string $from,
        public ?string $to,
        public string $search,
        public int $page,
        public int $perPage,
        public string $sort,
        public string $dir,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $serviceIdRaw = (int) ($payload['service_id'] ?? 0);

        $from = trim((string) ($payload['from'] ?? ''));
        $to = trim((string) ($payload['to'] ?? ''));

        $page = (int) ($payload['page'] ?? 1);
        if ($page <= 0) {
            $page = 1;
        }

        $perPageRaw = (int) ($payload['per_page'] ?? 25);
        $perPage = in_array($perPageRaw, self::PER_PAGE_OPTIONS, true) ? $perPageRaw : 25;

        $sortRaw = strtolower(trim((string) ($payload['sort'] ?? 'id')));
        $sort = in_array($sortRaw, self::SORT_OPTIONS, true) ? $sortRaw : 'id';

        $dirRaw = strtolower(trim((string) ($payload['dir'] ?? 'desc')));
        $dir = $dirRaw === 'asc' ? 'asc' : 'desc';

        return new self(
            serviceId: $serviceIdRaw > 0 ? $serviceIdRaw : null,
            from: $from !== '' ? $from : null,
            to: $to !== '' ? $to : null,
            search: trim((string) ($payload['q'] ?? '')),
            page: $page,
            perPage: $perPage,
            sort: $sort,
            dir: $dir,
        );
    }

    public function hasDateRange(): bool
    {
        return $this->from !== null && $this->to !== null;
    }

    public function withPage(int $page): self
    {
        return new self(
            serviceId: $this->serviceId,
            from: $this->from,
            to: $this->to,
            search: $this->search,
            page: max(1, $page),
            perPage: $this->perPage,
            sort: $this->sort,
            dir: $this->dir,
        );
    }
}
