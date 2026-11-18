<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_passkeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->binary('credential_id', 1024);
            $table->binary('public_key');
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->integer('algorithm')->nullable();
            $table->string('transports', 255)->nullable();
            $table->char('aaguid', 36)->nullable();
            $table->string('device_name', 120)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unique('credential_id', 'uk_user_passkeys_credential_id');
            $table->index('user_id', 'idx_user_passkeys_user_id');
            $table->index('revoked_at', 'idx_user_passkeys_revoked_at');
            $table->index('deleted_at', 'idx_user_passkeys_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_passkeys');
    }
};
