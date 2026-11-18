<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Issues;

use App\Enums\RoleKey;
use App\Models\IssueReport;
use App\Models\IssueReportAttachment;
use App\Models\IssueReportComment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_leader_can_list_issues_with_summary_and_meta(): void
    {
        $groupLeader = $this->createUserWithRole(RoleKey::GROUP_LEADER);
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        IssueReport::query()->create([
            'created_by' => $groupLeader->id,
            'module_key' => 'booking',
            'issue_key' => 'cannot_save',
            'priority' => 'urgent',
            'status' => 'open',
            'title' => 'Booking save failure',
            'description' => 'Cannot submit booking.',
            'assigned_to' => $manager->id,
        ]);

        IssueReport::query()->create([
            'created_by' => $manager->id,
            'module_key' => 'reports',
            'issue_key' => 'wrong_data_displayed',
            'priority' => 'normal',
            'status' => 'in_progress',
            'title' => 'Totals mismatch',
            'description' => 'Report total is wrong.',
        ]);

        $this->actingAs($groupLeader);

        $response = $this->getJson('/api/v1/issues');

        $response
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('data.summary.total_count', 2)
            ->assertJsonPath('data.summary.open_count', 1)
            ->assertJsonPath('data.summary.in_progress_count', 1)
            ->assertJsonPath('data.summary.urgent_count', 1)
            ->assertJsonPath('errors', []);
    }

    public function test_driver_cannot_access_issues_module(): void
    {
        $driver = $this->createUserWithRole(RoleKey::DRIVER);

        $this->actingAs($driver);

        $response = $this->getJson('/api/v1/issues');

        $response
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'issue_forbidden');
    }

    public function test_user_can_create_issue_with_screenshot_and_assignment_notification(): void
    {
        Storage::fake('local');

        $reporter = $this->createUserWithRole(RoleKey::GROUP_LEADER);
        $assignee = $this->createUserWithRole(RoleKey::MANAGER);

        $this->actingAs($reporter);

        $response = $this->post('/api/v1/issues', [
            'module_key' => 'booking',
            'issue_key' => 'cannot_save',
            'priority' => 'urgent',
            'title' => 'Booking cannot be saved',
            'description' => 'Save button throws an error.',
            'page_url' => '/crm/bookings',
            'assigned_to' => $assignee->id,
            'screenshots' => [
                $this->pngUpload('issue.png'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('errors', []);

        $issueId = (int) $response->json('data.id');
        $this->assertTrue($issueId > 0);

        $this->assertDatabaseHas('issue_reports', [
            'id' => $issueId,
            'created_by' => $reporter->id,
            'assigned_to' => $assignee->id,
            'status' => 'open',
        ]);

        $attachment = IssueReportAttachment::query()->where('issue_id', $issueId)->first();
        $this->assertNotNull($attachment);
        $this->assertTrue(Storage::disk('local')->exists('issue_attachments/'.$attachment->stored_name));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $assignee->id,
            'entity_type' => 'issue',
            'entity_id' => $issueId,
            'action_key' => 'assigned',
        ]);
    }

    public function test_non_creator_cannot_edit_issue_but_super_admin_can(): void
    {
        $creator = $this->createUserWithRole(RoleKey::GROUP_LEADER);
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $superAdmin = $this->createUserWithRole(RoleKey::SUPER_ADMIN);
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        $issue = IssueReport::query()->create([
            'created_by' => $creator->id,
            'module_key' => 'services',
            'issue_key' => 'cannot_save',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Service update blocked',
            'description' => 'Cannot update service.',
        ]);

        $this->actingAs($admin);

        $forbiddenResponse = $this->patchJson("/api/v1/issues/{$issue->id}/status", [
            'status' => 'in_progress',
        ]);

        $forbiddenResponse
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'issue_edit_forbidden');

        $this->actingAs($superAdmin);

        $allowedResponse = $this->patchJson("/api/v1/issues/{$issue->id}/assignee", [
            'assigned_to' => $manager->id,
        ]);

        $allowedResponse
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $manager->id)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('issue_reports', [
            'id' => $issue->id,
            'assigned_to' => $manager->id,
        ]);
    }

    public function test_creator_status_update_creates_comment_and_notifications(): void
    {
        $creator = $this->createUserWithRole(RoleKey::GROUP_LEADER);
        $assignee = $this->createUserWithRole(RoleKey::MANAGER);

        $issue = IssueReport::query()->create([
            'created_by' => $creator->id,
            'module_key' => 'booking',
            'issue_key' => 'cannot_save',
            'priority' => 'normal',
            'status' => 'open',
            'title' => 'Status flow test',
            'description' => 'Testing status update.',
            'assigned_to' => $assignee->id,
        ]);

        $this->actingAs($creator);

        $firstResponse = $this->patchJson("/api/v1/issues/{$issue->id}/status", [
            'status' => 'in_progress',
            'comment' => 'Investigating now.',
        ]);

        $firstResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('errors', []);

        $comment = IssueReportComment::query()
            ->where('issue_id', $issue->id)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($comment);
        $this->assertStringContainsString('Status changed: open -> in_progress', (string) $comment->comment_text);
        $this->assertStringContainsString('Investigating now.', (string) $comment->comment_text);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $assignee->id,
            'entity_type' => 'issue',
            'entity_id' => $issue->id,
            'action_key' => 'status_changed',
        ]);

        $secondResponse = $this->patchJson("/api/v1/issues/{$issue->id}/status", [
            'status' => 'in_progress',
            'comment' => 'Need logs from booking workflow.',
        ]);

        $secondResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $assignee->id,
            'entity_type' => 'issue',
            'entity_id' => $issue->id,
            'action_key' => 'comment_added',
        ]);
    }

    public function test_comment_endpoint_notifies_all_involved_users_except_actor(): void
    {
        $creator = $this->createUserWithRole(RoleKey::GROUP_LEADER);
        $assignee = $this->createUserWithRole(RoleKey::MANAGER);
        $commenter = $this->createUserWithRole(RoleKey::ADMIN);

        $issue = IssueReport::query()->create([
            'created_by' => $creator->id,
            'module_key' => 'reports',
            'issue_key' => 'wrong_data_displayed',
            'priority' => 'normal',
            'status' => 'open',
            'title' => 'Comment notification test',
            'description' => 'Testing comment notifications.',
            'assigned_to' => $assignee->id,
        ]);

        $this->actingAs($commenter);

        $response = $this->postJson("/api/v1/issues/{$issue->id}/comments", [
            'comment' => 'Please verify and confirm.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $issue->id)
            ->assertJsonPath('errors', []);

        $recipientCount = UserNotification::query()
            ->where('entity_type', 'issue')
            ->where('entity_id', $issue->id)
            ->where('action_key', 'comment_added')
            ->whereIn('user_id', [$creator->id, $assignee->id])
            ->count();

        $this->assertSame(2, $recipientCount);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $commenter->id,
            'entity_type' => 'issue',
            'entity_id' => $issue->id,
            'action_key' => 'comment_added',
        ]);
    }

    public function test_attachment_can_be_opened_inline_and_downloaded(): void
    {
        Storage::fake('local');

        $reporter = $this->createUserWithRole(RoleKey::GROUP_LEADER);

        $this->actingAs($reporter);

        $createResponse = $this->post('/api/v1/issues', [
            'module_key' => 'booking',
            'issue_key' => 'cannot_save',
            'priority' => 'normal',
            'description' => 'Attachment test.',
            'screenshots' => [
                $this->pngUpload('attachment.png'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $createResponse->assertCreated();

        $issueId = (int) $createResponse->json('data.id');
        $attachment = IssueReportAttachment::query()->where('issue_id', $issueId)->first();
        $this->assertNotNull($attachment);

        $inlineResponse = $this->get("/api/v1/issues/attachments/{$attachment->id}");
        $inlineResponse->assertOk();
        $this->assertStringContainsString(
            'inline',
            (string) $inlineResponse->headers->get('content-disposition'),
        );

        $downloadResponse = $this->get("/api/v1/issues/attachments/{$attachment->id}?download=1");
        $downloadResponse->assertOk();
        $this->assertStringContainsString(
            'attachment',
            (string) $downloadResponse->headers->get('content-disposition'),
        );
    }

    private function createUserWithRole(RoleKey $roleKey): User
    {
        $role = Role::query()->firstOrCreate(
            ['role_key' => $roleKey->value],
            RoleCatalog::payload($roleKey, 1),
        );

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }
}
