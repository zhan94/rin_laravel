<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user');
            $table->string('page', 255);
            $table->string('ip', 111);
            $table->string('location', 255)->nullable();
            $table->timestamp('date')->useCurrent()->useCurrentOnUpdate();

            $table->index(['user', 'date'], 'idx_logs_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
