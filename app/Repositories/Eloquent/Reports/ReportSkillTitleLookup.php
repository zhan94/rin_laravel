<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ReportSkillTitleLookup
{
    private ?bool $skillsTableExists = null;

    private ?string $skillsTitleColumn = null;

    /**
     * @param  list<int>  $skillIds
     * @return array<int,string>
     */
    public function map(array $skillIds): array
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $skillIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($normalized === [] || ! $this->skillsTableExists()) {
            return [];
        }

        $titleColumn = $this->skillsTitleColumn();
        if ($titleColumn === null) {
            return [];
        }

        return DB::table('skills')
            ->whereIn('id', $normalized)
            ->get(['id', $titleColumn])
            ->mapWithKeys(static function (object $row) use ($titleColumn): array {
                $id = (int) ($row->id ?? 0);
                $title = trim((string) ($row->{$titleColumn} ?? ''));

                if ($id <= 0 || $title === '') {
                    return [];
                }

                return [$id => $title];
            })
            ->all();
    }

    private function skillsTableExists(): bool
    {
        if ($this->skillsTableExists !== null) {
            return $this->skillsTableExists;
        }

        $this->skillsTableExists = Schema::hasTable('skills');

        return $this->skillsTableExists;
    }

    private function skillsTitleColumn(): ?string
    {
        if ($this->skillsTitleColumn !== null) {
            return $this->skillsTitleColumn;
        }

        if (! $this->skillsTableExists()) {
            return null;
        }

        foreach (['title', 'title_en', 'name'] as $column) {
            if (Schema::hasColumn('skills', $column)) {
                $this->skillsTitleColumn = $column;

                return $this->skillsTitleColumn;
            }
        }

        return null;
    }
}
