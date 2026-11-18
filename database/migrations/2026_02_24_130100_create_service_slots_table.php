<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->string('name', 255)->default('');
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->integer('max')->default(0);
            $table->string('season_type', 32)->nullable()->default('other');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['service_id', 'date_start', 'date_end'], 'idx_slots_service_range');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_slots');
    }
};
