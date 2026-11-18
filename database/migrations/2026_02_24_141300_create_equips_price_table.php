<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equips_price', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('equip_id')
                ->constrained('equips')
                ->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('days');
            $table->decimal('per_day', 10, 2)->default(0);

            $table->index(['equip_id', 'days'], 'idx_equips_price_equip_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equips_price');
    }
};
