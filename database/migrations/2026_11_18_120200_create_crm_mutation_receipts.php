<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_mutation_receipts', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('operation', 32);
            $table->uuid('request_key');
            $table->char('payload_hash', 64);
            $table->json('result');
            $table->timestamp('created_at');
            $table->primary(['user_id', 'operation', 'request_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_mutation_receipts');
    }
};
