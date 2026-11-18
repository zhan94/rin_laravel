<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('template_key', 100)->unique('uniq_template_key');
            $table->string('module_key', 50);
            $table->string('action_key', 50);
            $table->string('default_title', 190);
            $table->string('default_message', 500);
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->index(['module_key', 'action_key'], 'idx_module_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
