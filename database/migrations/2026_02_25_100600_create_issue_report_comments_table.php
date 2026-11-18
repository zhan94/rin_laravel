<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_report_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')
                ->constrained('issue_reports')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->text('comment_text');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('issue_id', 'idx_issue_report_comments_issue');
            $table->index('user_id', 'idx_issue_report_comments_user');
            $table->index('created_at', 'idx_issue_report_comments_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_report_comments');
    }
};
