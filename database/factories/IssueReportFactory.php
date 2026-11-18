<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\IssueReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueReport>
 */
class IssueReportFactory extends Factory
{
    protected $model = IssueReport::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'module_key' => 'booking',
            'issue_key' => 'ui_bug',
            'priority' => IssuePriority::NORMAL->value,
            'status' => IssueStatus::OPEN->value,
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'page_url' => fake()->optional()->url(),
            'resolved_by' => null,
            'assigned_to' => null,
            'assigned_at' => null,
            'resolved_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
