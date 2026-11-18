<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

final readonly class LegacyTable
{
    /**
     * @param  list<string>  $keys
     * @param  array<string, string>  $renames
     * @param  list<string>  $checkedInRelationships
     * @param  list<list<string>>  $unique
     */
    public function __construct(
        public string $source,
        public string $columns,
        public ?string $destination = null,
        public array $keys = ['id'],
        public array $renames = [],
        public array $checkedInRelationships = [],
        public array $unique = [],
    ) {}

    public function target(): string
    {
        return $this->destination ?? $this->source;
    }

    /** @return list<string> */
    public function sourceColumns(): array
    {
        return explode(' ', $this->columns);
    }
}
