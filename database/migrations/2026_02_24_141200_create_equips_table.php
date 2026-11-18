<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equips', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 100)->default('');
            $table->string('title_bg', 255)->nullable();
            $table->string('title_en', 255)->nullable();
            $table->foreignId('service')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->index('service', 'idx_equips_service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equips');
    }
};
