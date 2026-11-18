<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

use Closure;
use DomainException;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use stdClass;

final readonly class LegacyImporter
{
    public function __construct(
        private LegacyImportPlan $plan,
        private LegacyRowValues $values,
        private LegacyIntegrityChecks $integrity,
        private LegacyAttachments $attachments,
    ) {}

    /**
     * @param  Closure(string,int,int):void  $progress
     */
    public function run(Connection $source, Connection $target, int $chunk, bool $dryRun, bool $replaceSeason, Closure $progress): LegacyImportReport
    {
        if ($source === $target || $chunk < 1 || $chunk > 5000) {
            throw new DomainException('The import requires distinct connections and a chunk size between 1 and 5000.');
        }

        $start = hrtime(true);
        $report = new LegacyImportReport;
        $source->transaction(function () use ($source, $target, $chunk, $dryRun, $replaceSeason, $progress, $report): void {
            $target->transaction(function () use ($source, $target, $chunk, $dryRun, $replaceSeason, $progress, $report): void {
                $tables = $this->plan->tables();
                $catalog = $this->reviewSchema($source, $target, $tables, $report);
                if ($report->errors !== []) {
                    return;
                }

                $this->integrity->check($source, $report, source: true);
                foreach ($tables as $table) {
                    $expected = $source->table($table->source)->count();
                    $before = $target->table($table->target())->count();
                    $report->tables[$table->target()] = [
                        'source' => $expected, 'before' => $before, 'matched' => 0, 'new' => 0, 'replace' => 0,
                        'invalid' => 0, 'after' => $before,
                    ];
                    $seen = 0;

                    foreach ($this->batches($source, $table, $chunk) as $batch) {
                        $rows = $this->mappedRows($batch, $table, $catalog[$table->target()], $report);
                        $result = $this->compare($target, $table, $rows, $catalog[$table->target()], $replaceSeason, $report);
                        foreach ($result as $outcome => $items) {
                            $report->tables[$table->target()][$outcome] += count($items);
                        }
                        $seen += $batch->count();
                        $report->tables[$table->target()]['invalid'] += $batch->count() - array_sum(array_map('count', $result));
                        $progress($table->target(), $seen, $expected);
                    }

                    if ($seen !== $expected) {
                        $report->error($table->target(), 'source count changed during scan');
                    }
                }

                if ($dryRun || $report->errors !== []) {
                    return;
                }

                foreach ($tables as $table) {
                    $this->writeTable($source, $target, $table, $catalog[$table->target()], $chunk, $replaceSeason, $progress, $report);
                }
                $this->integrity->check($target, $report, source: false);
                if ($report->errors !== []) {
                    throw new DomainException('Destination integrity verification failed; database changes were rolled back.');
                }
            });
        });
        $report->seconds = (hrtime(true) - $start) / 1e9;

        return $report;
    }

    /**
     * @param  list<LegacyTable>  $tables
     * @return array<string,array<string,array<string,mixed>>>
     */
    private function reviewSchema(Connection $source, Connection $target, array $tables, LegacyImportReport $report): array
    {
        $sourceSchema = $source->getSchemaBuilder();
        $targetSchema = $target->getSchemaBuilder();
        $known = array_merge(array_keys(LegacyImportPlan::EXCLUDED), array_map(fn (LegacyTable $table) => $table->source, $tables));
        $catalog = [];

        $schema = $source->getDriverName() === 'mysql' ? $source->getDatabaseName() : null;
        $sourceTables = collect($sourceSchema->getTables($schema))->keyBy('name');
        $targetSchemaName = $target->getDriverName() === 'mysql' ? $target->getDatabaseName() : null;
        $targetTables = collect($targetSchema->getTables($targetSchemaName))->keyBy('name');
        foreach (array_diff($sourceTables->keys()->all(), $known) as $extra) {
            $report->error('schema', 'unmapped source table '.$extra);
        }

        foreach (array_keys(LegacyImportPlan::EXCLUDED) as $table) {
            if ($sourceTables->has($table)) {
                $report->exclusions[$table] = $source->table($table)->count();
            }
        }

        foreach ($tables as $table) {
            if (! $sourceTables->has($table->source)) {
                $report->error($table->source, 'missing source table');

                continue;
            }
            if ($schema !== null && ($sourceTables->get($table->source)['engine'] ?? null) !== 'InnoDB') {
                $report->error($table->source, 'source must use InnoDB for a consistent snapshot');
            }
            $columns = $sourceSchema->getColumnListing($table->source);
            foreach (array_diff($columns, $table->sourceColumns()) as $extra) {
                $report->error($table->source, 'unmapped source column '.$extra);
            }
            foreach (array_diff($table->sourceColumns(), $columns) as $missing) {
                $report->error($table->source, 'missing source column '.$missing);
            }
            $indexes = collect($sourceSchema->getIndexes($table->source));
            $primary = $indexes->firstWhere('primary', true);
            if (($primary['columns'] ?? []) !== $table->keys) {
                $report->error($table->source, 'source primary key does not match the mapping');
            }
            if (! $targetTables->has($table->target())) {
                $report->error($table->target(), 'missing destination table');

                continue;
            }
            if ($targetSchemaName !== null && ($targetTables->get($table->target())['engine'] ?? null) !== 'InnoDB') {
                $report->error($table->target(), 'destination must use InnoDB for transactional import');
            }
            $catalog[$table->target()] = array_column($targetSchema->getColumns($table->target()), null, 'name');
            $targetIndexes = collect($targetSchema->getIndexes($table->target()));
            foreach ([$table->keys, ...$table->unique] as $unique) {
                if (! $indexes->contains(fn (array $index) => $index['unique'] && $index['columns'] === $unique)
                    || ! $targetIndexes->contains(fn (array $index) => $index['unique'] && $index['columns'] === $unique)) {
                    $report->error($table->target(), 'required unique key is missing: '.implode('+', $unique));
                }
            }
        }

        return $catalog;
    }

    /** @return iterable<Enumerable<int,stdClass>> */
    private function batches(Connection $source, LegacyTable $table, int $chunk): iterable
    {
        $query = $source->table($table->source)->select($table->sourceColumns());
        if (count($table->keys) === 1) {
            yield from $query->lazyById($chunk, $table->keys[0])->chunk($chunk);

            return;
        }

        $last = null;
        do {
            $page = clone $query;
            foreach ($table->keys as $position => $column) {
                $page->orderBy($column);
            }
            if ($last !== null) {
                // The OR form makes the individual prefixes eligible for the composite key index.
                $page->where(function (Builder $any) use ($table, $last): void {
                    foreach ($table->keys as $position => $column) {
                        $any->orWhere(function (Builder $part) use ($table, $last, $position, $column): void {
                            foreach (array_slice($table->keys, 0, $position) as $prefix) {
                                $part->where($prefix, $last->{$prefix});
                            }
                            $part->where($column, '>', $last->{$column});
                        });
                    }
                });
            }
            $batch = $page->limit($chunk)->get();
            if ($batch->isNotEmpty()) {
                yield $batch;
                $last = $batch->last();
            }
        } while ($batch->count() === $chunk);
    }

    /**
     * @param  iterable<stdClass>  $batch
     * @param  array<string,array<string,mixed>>  $columns
     * @return list<array<string,mixed>>
     */
    private function mappedRows(iterable $batch, LegacyTable $table, array $columns, LegacyImportReport $report): array
    {
        $rows = [];
        foreach ($batch as $source) {
            try {
                $row = $this->values->normalize($this->plan->map($table, $source), $columns);
                if (count($table->keys) === 1 && (int) $row[$table->keys[0]] < 1) {
                    throw new DomainException('primary identifier must be positive');
                }
                if ($table->source === 'issue_report_attachments') {
                    $this->attachments->check($row['stored_name'], (int) $row['file_size'], copy: false);
                }
                $rows[] = $row;
            } catch (DomainException $exception) {
                $report->error($table->source, $exception->getMessage());
            }
        }

        return $rows;
    }

    /**
     * @param  list<array<string,mixed>>  $rows
     * @param  array<string,array<string,mixed>>  $columns
     * @return array<string,list<array<string,mixed>>>
     */
    private function compare(Connection $target, LegacyTable $table, array $rows, array $columns, bool $replaceSeason, LegacyImportReport $report): array
    {
        $result = ['matched' => [], 'new' => [], 'replace' => []];
        if ($rows === []) {
            return $result;
        }

        $valueColumns = array_keys($rows[0]);
        $existing = $this->lookup($target, $table->target(), $table->keys, $rows, $valueColumns)
            ->keyBy(fn (stdClass $row) => $this->key((array) $row, $table->keys));
        $keys = [];
        foreach ($rows as $row) {
            $key = $this->key($row, $table->keys);
            $keys[$key] = true;
            $stored = $existing->get($key);
            if ($stored === null) {
                $result['new'][] = $row;
            } else {
                $canonical = $this->values->normalize((array) $stored, $columns);
                if ($this->values->identical($row, $canonical)) {
                    $result['matched'][] = $row;
                } elseif ($table->source === 'season_defaults' && $this->isSeededSeason($canonical)) {
                    if ($replaceSeason) {
                        $result['replace'][] = $row;
                    } else {
                        $report->error($table->target(), 'migration-seeded row differs; explicit replacement approval is required');
                    }
                } else {
                    $report->error($table->target(), 'existing primary key has different data');
                }
            }
        }
        if (array_diff_key($existing->all(), $keys) !== []) {
            $report->error($table->target(), 'existing primary key collides under the destination collation');
        }

        foreach ($table->unique as $unique) {
            $candidates = array_values(array_filter($rows, fn (array $row) => count(array_filter($unique, fn (string $name) => $row[$name] === null)) === 0));
            if ($candidates === []) {
                continue;
            }
            foreach ($this->lookup($target, $table->target(), $unique, $candidates, $table->keys) as $collision) {
                if (! isset($keys[$this->key((array) $collision, $table->keys)])) {
                    $report->error($table->target(), 'different primary key occupies unique '.implode('+', $unique));
                }
            }
        }

        return $result;
    }

    /** @param array<string,array<string,mixed>> $columns */
    private function writeTable(Connection $source, Connection $target, LegacyTable $table, array $columns, int $chunk, bool $replaceSeason, Closure $progress, LegacyImportReport $report): void
    {
        $name = $table->target();
        $stats = &$report->tables[$name];
        $written = 0;
        $seen = 0;
        foreach ($this->batches($source, $table, $chunk) as $batch) {
            $rows = $this->mappedRows($batch, $table, $columns, $report);
            $result = $this->compare($target, $table, $rows, $columns, $replaceSeason, $report);
            if ($report->errors !== []) {
                throw new DomainException($name.' failed write-phase validation. Database changes were rolled back. '.implode(', ', array_keys($report->errors)));
            }

            foreach (array_chunk($result['new'], 250) as $insert) {
                $target->table($name)->insert($insert);
                $written += count($insert);
            }
            foreach ($result['replace'] as $row) {
                $revision = $target->table($name)->where('id', 1)->value('updated_at');
                $updated = $target->table($name)->where([
                    'id' => 1, 'start_month' => 9, 'start_day' => 1, 'end_month' => 8, 'end_day' => 31,
                    'updated_at' => $revision,
                ])->update(array_diff_key($row, ['id' => true]));
                if ($updated !== 1) {
                    throw new DomainException('The season default changed during import; database changes were rolled back.');
                }
            }
            if ($table->source === 'issue_report_attachments') {
                foreach ($rows as $row) {
                    $this->attachments->check($row['stored_name'], (int) $row['file_size'], copy: true);
                }
            }

            $verified = $this->compare($target, $table, $rows, $columns, false, $report);
            if (count($verified['matched']) !== count($rows) || $report->hasErrors()) {
                throw new DomainException($name.' failed row-by-row verification; database changes were rolled back.');
            }
            $seen += count($rows);
            $progress('Import '.$name, $seen, $stats['source']);
        }

        $stats['after'] = $target->table($name)->count();
        if ($written !== $stats['new'] || $seen !== $stats['source'] || $stats['after'] !== $stats['before'] + $written) {
            throw new DomainException($name.' failed count reconciliation; database changes were rolled back.');
        }
    }

    /**
     * @param  list<string>  $keys
     * @param  list<array<string,mixed>>  $rows
     * @param  list<string>  $select
     * @return Collection<int,stdClass>
     */
    private function lookup(Connection $target, string $table, array $keys, array $rows, array $select): Collection
    {
        $matches = collect();
        foreach (array_chunk($rows, 250) as $page) {
            $query = $target->table($table)->select($select);
            if (count($keys) === 1) {
                $query->whereIn($keys[0], array_column($page, $keys[0]));
            } else {
                $query->where(function (Builder $any) use ($keys, $page): void {
                    foreach ($page as $row) {
                        $any->orWhere(fn (Builder $match) => $match->where(array_intersect_key($row, array_flip($keys))));
                    }
                });
            }
            $matches = $matches->concat($query->get());
        }

        return $matches;
    }

    /**
     * @param  array<string,mixed>  $row
     * @param  list<string>  $columns
     */
    private function key(array $row, array $columns): string
    {
        return json_encode(array_map(fn (string $column) => (string) $row[$column], $columns), JSON_THROW_ON_ERROR);
    }

    /** @param array<string,mixed> $row */
    private function isSeededSeason(array $row): bool
    {
        return $row['id'] === '1' && $row['start_month'] === '9' && $row['start_day'] === '1'
            && $row['end_month'] === '8' && $row['end_day'] === '31' && $row['updated_at'] !== null;
    }
}
