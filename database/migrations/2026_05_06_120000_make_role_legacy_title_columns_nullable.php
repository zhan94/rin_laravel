<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('title_bg', 120)->nullable()->change();
            $table->string('title_en', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('title_bg', 120)->nullable(false)->change();
            $table->string('title_en', 120)->nullable(false)->change();
        });
    }
};
