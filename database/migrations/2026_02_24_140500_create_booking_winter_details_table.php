<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_winter_details', function (Blueprint $table): void {
            $table->unsignedBigInteger('booking_id')->primary();
            $table->unsignedBigInteger('skill_id')->nullable();
            $table->unsignedBigInteger('equip_id')->nullable();
            $table->unsignedBigInteger('card_id')->nullable();
            $table->unsignedInteger('shoe_size')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')
                ->references('id')
                ->on('bookings')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_winter_details');
    }
};
