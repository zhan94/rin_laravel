<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Issues;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\IssueReportAttachment */
class IssueAttachmentResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $baseUrl = '/api/v1/issues/attachments/'.(int) $this->id;

        return [
            'id' => (int) $this->id,
            'issue_id' => (int) $this->issue_id,
            'original_name' => (string) $this->original_name,
            'mime_type' => (string) $this->mime_type,
            'file_size' => (int) $this->file_size,
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
            'url' => $baseUrl,
            'download_url' => $baseUrl.'?download=1',
        ];
    }
}
