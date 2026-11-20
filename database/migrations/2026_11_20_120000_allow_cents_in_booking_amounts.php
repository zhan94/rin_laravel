<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['bookings', 'booking_payments'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->decimal('sum_paid', 12, 2)->nullable()->change();
                $table->decimal('paid', 12, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['booking_payments', 'bookings'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->integer('sum_paid')->nullable()->change();
                $table->integer('paid')->nullable()->change();
            });
        }
    }
};
