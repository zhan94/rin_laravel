<?php

declare(strict_types=1);

namespace App\Support\Catalog;

final readonly class CatalogCrudDescriptor
{
    public function __construct(
        public string $moduleKey,
        public string $titleEntityLabel,
        public string $messageEntityLabel,
        public string $entityType,
        public string $linkPath,
        public string $notFoundErrorCode,
        public string $notFoundMessage,
    ) {}
}
