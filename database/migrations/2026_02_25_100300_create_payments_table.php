<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('kid')->index();
            $table->decimal('sum_service', 10, 2)->default(0);
            $table->decimal('paid_service', 10, 2)->default(0);
            $table->decimal('equip_sum', 10, 2)->default(0);
            $table->decimal('card_sum', 10, 2)->default(0);
            $table->decimal('equip', 10, 2)->default(0);
            $table->decimal('card', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
