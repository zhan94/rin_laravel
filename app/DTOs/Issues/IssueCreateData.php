<?php

declare(strict_types=1);

namespace App\DTOs\Issues;

use Illuminate\Http\UploadedFile;

final readonly class IssueCreateData
{
    /**
     * @param  list<UploadedFile>  $screenshots
     */
    public function __construct(
        public string $moduleKey,
        public string $issueKey,
        public string $priority,
        public string $title,
        public string $description,
        public ?string $pageUrl,
        public ?int $assignedTo,
        public array $screenshots,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $title = trim((string) ($payload['title'] ?? ''));

        $screenshotsRaw = $payload['screenshots'] ?? [];
        $screenshots = [];
        if (is_array($screenshotsRaw)) {
            foreach ($screenshotsRaw as $file) {
                if ($file instanceof UploadedFile) {
                    $screenshots[] = $file;
                }
            }
        }

        $assignedToRaw = (int) ($payload['assigned_to'] ?? 0);
        $pageUrl = trim((string) ($payload['page_url'] ?? ''));

        return new self(
            moduleKey: trim((string) ($payload['module_key'] ?? '')),
            issueKey: trim((string) ($payload['issue_key'] ?? '')),
            priority: trim((string) ($payload['priority'] ?? 'normal')),
            title: $title,
            description: trim((string) ($payload['description'] ?? '')),
            pageUrl: $pageUrl !== '' ? $pageUrl : null,
            assignedTo: $assignedToRaw > 0 ? $assignedToRaw : null,
            screenshots: $screenshots,
        );
    }
}
