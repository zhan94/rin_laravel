<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssueReport extends Model
{
    /** @use HasFactory<\Database\Factories\IssueReportFactory> */
    use HasFactory;

    protected $table = 'issue_reports';

    protected $fillable = [
        'created_by',
        'module_key',
        'issue_key',
        'priority',
        'status',
        'title',
        'description',
        'page_url',
        'resolved_by',
        'assigned_to',
        'assigned_at',
        'resolved_at',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'created_by' => 'integer',
            'resolved_by' => 'integer',
            'assigned_to' => 'integer',
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'priority' => IssuePriority::class,
            'status' => IssueStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<IssueReportComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(IssueReportComment::class, 'issue_id')->orderBy('created_at');
    }

    /**
     * @return HasMany<IssueReportAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(IssueReportAttachment::class, 'issue_id')->orderBy('created_at');
    }
}
