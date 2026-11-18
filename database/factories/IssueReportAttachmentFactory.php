<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IssueReport;
use App\Models\IssueReportAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueReportAttachment>
 */
class IssueReportAttachmentFactory extends Factory
{
    protected $model = IssueReportAttachment::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => IssueReport::factory(),
            'stored_name' => fake()->uuid().'.txt',
            'original_name' => fake()->word().'.txt',
            'mime_type' => 'text/plain',
            'file_size' => fake()->numberBetween(120, 4096),
            'created_at' => now(),
        ];
    }
}
