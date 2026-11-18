<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /**
     * @param  array<string,mixed>  $details
     */
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly int $statusCode = 422,
        private readonly array $details = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string,mixed>
     */
    public function details(): array
    {
        return $this->details;
    }
}
