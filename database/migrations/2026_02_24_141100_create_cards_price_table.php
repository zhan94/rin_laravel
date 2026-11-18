<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards_price', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('card_id')
                ->constrained('cards')
                ->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('days');
            $table->decimal('per_day', 10, 2)->default(0);

            $table->index(['card_id', 'days'], 'idx_cards_price_card_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards_price');
    }
};
