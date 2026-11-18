<?php

declare(strict_types=1);

namespace App\DTOs\Issues;

final readonly class IssueStatusUpdateData
{
    public function __construct(
        public string $status,
        public ?string $comment,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $comment = trim((string) ($payload['comment'] ?? ''));

        return new self(
            status: trim((string) ($payload['status'] ?? 'open')),
            comment: $comment !== '' ? $comment : null,
        );
    }
}
