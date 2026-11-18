<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_dates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('slot_id')
                ->constrained('service_slots')
                ->cascadeOnDelete();
            $table->date('slot_date');
            $table->boolean('is_active')->default(true)->index();

            $table->unique(['slot_id', 'slot_date'], 'uq_slot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_dates');
    }
};
