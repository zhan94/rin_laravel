<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 222)->default('');
            $table->string('title_bg', 255)->nullable();
            $table->string('title_en', 255)->nullable();
            $table->unsignedInteger('title_key')->nullable();
            $table->string('location_from', 222)->default('');
            $table->string('location_to', 222)->default('');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();

            $table->index(['title_bg', 'title_en'], 'idx_bonus_titles');
            $table->index(['date_from', 'date_to'], 'idx_bonus_date_range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus');
    }
};
