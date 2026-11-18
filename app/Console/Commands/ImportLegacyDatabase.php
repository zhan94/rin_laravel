<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LegacyImport\LegacyImporter;
use App\Services\LegacyImport\LegacyImportPlan;
use App\Services\LegacyImport\LegacyImportReport;
use DomainException;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

final class ImportLegacyDatabase extends Command
{
    protected $signature = 'legacy:import
        {--dry-run : Validate mappings, relationships and destination conflicts without writing}
        {--chunk=1000 : Source rows per batch, from 1 to 5000}
        {--replace-season-defaults : Permit replacing the exact migration-seeded season calendar}';

    protected $description = 'Validate and import the legacy CRM into rin_lara';

    public function handle(DatabaseManager $databases, LegacyImporter $importer): int
    {
        $chunk = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5000]]);
        if ($chunk === false) {
            $this->error('--chunk must be an integer between 1 and 5000.');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $replaceSeason = (bool) $this->option('replace-season-defaults');
        $source = $databases->connection('legacy');
        $target = $databases->connection();
        $lock = 'ski2:legacy:import:rin_lara';
        $locked = false;

        try {
            $this->checkConnections($source, $target);
            if (! $dryRun && (! $this->input->isInteractive() || ! $this->confirm(
                'Import this approved dataset into rin_lara?'.($replaceSeason ? ' This includes replacing the migration-seeded season calendar.' : '')
            ))) {
                $this->error('Database writes require interactive approval for the current dataset.');

                return self::FAILURE;
            }

            if (! $dryRun) {
                $locked = (int) $target->scalar('SELECT GET_LOCK(?, 0)', [$lock], false) === 1;
                if (! $locked) {
                    $this->error('Another legacy import holds the destination lock.');

                    return self::FAILURE;
                }
            }

            $this->info($dryRun ? 'Validating briliabg_shadmin → rin_lara' : 'Importing briliabg_shadmin → rin_lara');
            $report = $importer->run($source, $target, $chunk, $dryRun, $replaceSeason, function (string $table, int $done, int $total): void {
                if ($done === $total || ($done > 0 && $done % 10000 === 0)) {
                    $this->line(sprintf('%s  %d/%d', $table, $done, $total));
                }
            });
            $this->showReport($report, $dryRun);

            return $report->errors === [] ? self::SUCCESS : self::FAILURE;
        } catch (QueryException|PDOException $exception) {
            // SQL exception text can contain bound names, contacts, hashes or free text.
            $code = $exception->errorInfo[0] ?? $exception->getCode();
            $driverCode = $exception->errorInfo[1] ?? 'unknown';
            $this->error(sprintf('Database operation failed (SQLSTATE %s, driver code %s). The destination transaction did not complete.', $code, $driverCode));

            return self::FAILURE;
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('Import stopped due to '.get_class($exception).'. Dynamic database values were omitted from the error output.');

            return self::FAILURE;
        } finally {
            if ($locked) {
                $target->scalar('SELECT RELEASE_LOCK(?)', [$lock], false);
            }
        }
    }

    private function checkConnections(Connection $source, Connection $target): void
    {
        if ($target->getName() !== 'mysql' || $target->getDatabaseName() !== 'rin_lara'
            || $source->getName() !== 'legacy' || $source->getDriverName() !== 'mysql'
            || $source->getDatabaseName() !== 'briliabg_shadmin') {
            throw new DomainException('Expected legacy/briliabg_shadmin and default mysql/rin_lara connections.');
        }

        $from = $source->selectOne('SELECT DATABASE() AS schema_name, @@transaction_read_only AS read_only, @@transaction_isolation AS isolation_mode, @@character_set_connection AS encoding', [], false);
        $to = $target->selectOne('SELECT DATABASE() AS schema_name, @@foreign_key_checks AS foreign_keys, @@character_set_connection AS encoding, @@sql_mode AS sql_mode', [], false);

        if ($from->schema_name !== 'briliabg_shadmin' || $to->schema_name !== 'rin_lara'
            || (int) $from->read_only !== 1 || $from->isolation_mode !== 'REPEATABLE-READ'
            || (int) $to->foreign_keys !== 1 || $from->encoding !== 'utf8mb4' || $to->encoding !== 'utf8mb4'
            || ! preg_match('/\bSTRICT_(?:ALL|TRANS)_TABLES\b/', $to->sql_mode)) {
            throw new DomainException('Database safety checks failed. Verify schema names, read-only snapshot, strict mode, foreign keys and utf8mb4.');
        }

        // TIMESTAMP columns must render the same instant in both sessions.
        $target->statement("SET SESSION time_zone = '+00:00'");
    }

    private function showReport(LegacyImportReport $report, bool $dryRun): void
    {
        $rows = [];
        foreach ($report->tables as $table => $stats) {
            $rows[] = [$table, ...array_values($stats)];
        }
        $this->table(['Table', 'Source', 'Before', 'Matched', $dryRun ? 'Would insert' : 'New', 'Replace', 'Invalid', 'After'], $rows);

        foreach ($report->exclusions as $table => $count) {
            $this->line(sprintf('Excluded %s (%d): %s', $table, $count, LegacyImportPlan::EXCLUDED[$table]));
        }
        foreach ($report->errors as $error => $count) {
            $this->error(sprintf('%s [%d]', $error, $count));
        }

        $this->line(sprintf('%d integrity checks, %.2f s, %.1f MiB peak PHP memory.', count($report->checks), $report->seconds, memory_get_peak_usage(true) / 1048576));
        $this->info(match (true) {
            $report->errors !== [] => 'Validation failed. No business rows were written.',
            $dryRun => 'Dry run passed. No business rows were written.',
            default => 'Import committed and destination integrity verified.',
        });
    }
}
