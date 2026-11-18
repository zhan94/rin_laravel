<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services_price', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('services')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('days');
            $table->decimal('per_day', 10, 2)->default(0);

            $table->index(['services', 'days'], 'idx_services_price_service_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services_price');
    }
};
