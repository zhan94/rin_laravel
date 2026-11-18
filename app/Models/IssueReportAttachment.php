<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueReportAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\IssueReportAttachmentFactory> */
    use HasFactory;

    protected $table = 'issue_report_attachments';

    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'stored_name',
        'original_name',
        'mime_type',
        'file_size',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'issue_id' => 'integer',
            'file_size' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<IssueReport, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(IssueReport::class, 'issue_id');
    }
}
