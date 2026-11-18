<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_dates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->foreignId('service_date_id')
                ->constrained('service_dates')
                ->cascadeOnDelete();

            $table->unique(['booking_id', 'service_date_id'], 'uq_booking_date_pair');
            $table->index('service_date_id', 'idx_booking_dates_service_date_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_dates');
    }
};
