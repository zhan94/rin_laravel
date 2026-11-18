<?php

declare(strict_types=1);

namespace App\DTOs\Issues;

final readonly class IssueAssigneeUpdateData
{
    public function __construct(
        public ?int $assignedTo,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $assignedToRaw = (int) ($payload['assigned_to'] ?? 0);

        return new self(
            assignedTo: $assignedToRaw > 0 ? $assignedToRaw : null,
        );
    }
}
