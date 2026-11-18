<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_defaults', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedTinyInteger('start_month');
            $table->unsignedTinyInteger('start_day');
            $table->unsignedTinyInteger('end_month');
            $table->unsignedTinyInteger('end_day');
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('season_defaults')->insert([
            'id' => 1,
            'start_month' => 9,
            'start_day' => 1,
            'end_month' => 8,
            'end_day' => 31,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('season_defaults');
    }
};
