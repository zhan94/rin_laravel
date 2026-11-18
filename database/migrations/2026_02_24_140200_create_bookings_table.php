<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->unsignedBigInteger('service')->nullable()->index();
            $table->unsignedTinyInteger('service_type')->nullable()->index();
            $table->integer('sum_paid')->nullable();
            $table->integer('paid')->nullable();
            $table->foreignId('location')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();
            $table->boolean('approved')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('lunch')->default(false);
            $table->longText('note')->nullable();

            $table->index(['service_type', 'service'], 'idx_bookings_service_type_service');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
