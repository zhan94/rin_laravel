<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('module_key', 64);
            $table->string('issue_key', 64);
            $table->enum('priority', ['normal', 'medium', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->string('title', 190);
            $table->text('description')->nullable();
            $table->string('page_url', 255)->nullable();
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('created_by', 'idx_issue_reports_created_by');
            $table->index('status', 'idx_issue_reports_status');
            $table->index('priority', 'idx_issue_reports_priority');
            $table->index('module_key', 'idx_issue_reports_module');
            $table->index('assigned_to', 'idx_issue_reports_assigned_to');
            $table->index('created_at', 'idx_issue_reports_created_at');
            $table->index('resolved_by', 'fk_issue_reports_resolved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_reports');
    }
};
