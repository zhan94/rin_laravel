<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->string('notification_type', 80);
            $table->string('module_key', 50)->default('');
            $table->string('action_key', 50)->default('');
            $table->string('entity_type', 50)->default('');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('title', 190);
            $table->string('message', 500);
            $table->json('metadata')->nullable();
            $table->string('link_path', 255)->default('');
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->string('delivery_channel', 40)->default('in_app');
            $table->boolean('is_sent')->default(true);
            $table->dateTime('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('read_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['user_id', 'status', 'created_at'], 'idx_user_status_created');
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index(['entity_type', 'entity_id'], 'idx_entity');
            $table->index('notification_type', 'idx_type');
            $table->index('created_at', 'idx_created');
            $table->index('actor_user_id', 'fk_user_notifications_actor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
