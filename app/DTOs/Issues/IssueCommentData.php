<?php

declare(strict_types=1);

namespace App\DTOs\Issues;

final readonly class IssueCommentData
{
    public function __construct(
        public string $comment,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            comment: trim((string) ($payload['comment'] ?? '')),
        );
    }
}
