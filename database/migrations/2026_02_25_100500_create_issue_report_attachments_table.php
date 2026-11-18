<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_report_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')
                ->constrained('issue_reports')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('stored_name', 190);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size')->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('issue_id', 'idx_issue_report_attachments_issue');
            $table->index('created_at', 'idx_issue_report_attachments_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_report_attachments');
    }
};
