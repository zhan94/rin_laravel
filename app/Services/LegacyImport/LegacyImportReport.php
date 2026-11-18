<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

final class LegacyImportReport
{
    /** @var array<string, array<string,int>> */
    public array $tables = [];

    /** @var array<string, int> */
    public array $errors = [];

    /** @var array<string, int> */
    public array $exclusions = [];

    /** @var array<string, int> */
    public array $checks = [];

    public float $seconds = 0;

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function error(string $scope, string $reason, int $count = 1): void
    {
        $key = $scope.' / '.$reason;
        $this->errors[$key] = ($this->errors[$key] ?? 0) + $count;
    }
}
