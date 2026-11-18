<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('location_from', 222);
            $table->string('location_to', 255);
            $table->string('from_time', 25);
            $table->string('to_time', 25);
            $table->unsignedBigInteger('service_id')->default(0)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
