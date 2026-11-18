<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IssueReport;
use App\Models\IssueReportComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueReportComment>
 */
class IssueReportCommentFactory extends Factory
{
    protected $model = IssueReportComment::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => IssueReport::factory(),
            'user_id' => User::factory(),
            'comment_text' => fake()->sentence(8),
            'created_at' => now(),
        ];
    }
}
