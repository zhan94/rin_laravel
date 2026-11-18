<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 100)->default('');
            $table->string('title_bg', 255)->nullable();
            $table->string('title_en', 255)->nullable();
            $table->unsignedInteger('title_key')->nullable();
            $table->integer('max')->default(0);
            $table->boolean('active')->default(true)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
