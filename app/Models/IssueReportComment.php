<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueReportComment extends Model
{
    /** @use HasFactory<\Database\Factories\IssueReportCommentFactory> */
    use HasFactory;

    protected $table = 'issue_report_comments';

    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'user_id',
        'comment_text',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'issue_id' => 'integer',
            'user_id' => 'integer',
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
