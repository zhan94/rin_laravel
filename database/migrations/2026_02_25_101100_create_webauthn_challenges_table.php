<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webauthn_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->binary('challenge', 255);
            $table->enum('challenge_type', ['register', 'authenticate']);
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->unique('challenge', 'uk_webauthn_challenges_challenge');
            $table->index('user_id', 'idx_webauthn_challenges_user_id');
            $table->index('expires_at', 'idx_webauthn_challenges_expires_at');
            $table->index('used_at', 'idx_webauthn_challenges_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webauthn_challenges');
    }
};
