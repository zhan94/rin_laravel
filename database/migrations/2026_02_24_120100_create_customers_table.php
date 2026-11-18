<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('firstname', 120);
            $table->string('surname', 120)->nullable();
            $table->string('lastname', 120);
            $table->date('date_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('parent_name', 191)->nullable();
            $table->string('parent_phone', 64)->nullable();
            $table->string('email', 255)->nullable()->index();
            $table->boolean('active')->default(true)->index();

            $table->index(['lastname', 'firstname'], 'idx_customers_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
