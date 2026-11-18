<?php

declare(strict_types=1);

namespace App\DTOs\Issues;

final readonly class IssueFilterData
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * @var list<string>
     */
    private const ALLOWED_SCOPES = ['all', 'assigned', 'created'];

    /**
     * @var list<string>
     */
    private const ALLOWED_STATUSES = ['all', 'open', 'in_progress', 'resolved'];

    public function __construct(
        public string $scope,
        public string $status,
        public string $module,
        public int $page,
        public int $perPage,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $scopeRaw = strtolower(trim((string) ($payload['scope'] ?? 'all')));
        $scope = in_array($scopeRaw, self::ALLOWED_SCOPES, true) ? $scopeRaw : 'all';

        $statusRaw = strtolower(trim((string) ($payload['status'] ?? 'all')));
        $status = in_array($statusRaw, self::ALLOWED_STATUSES, true) ? $statusRaw : 'all';

        $module = trim((string) ($payload['module'] ?? 'all'));
        if ($module === '') {
            $module = 'all';
        }

        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPageRaw = (int) ($payload['per_page'] ?? 25);
        $perPage = in_array($perPageRaw, self::PER_PAGE_OPTIONS, true)
            ? $perPageRaw
            : 25;

        return new self(
            scope: $scope,
            status: $status,
            module: $module,
            page: $page,
            perPage: $perPage,
        );
    }
}
