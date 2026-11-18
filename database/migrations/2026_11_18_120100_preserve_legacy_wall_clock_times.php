<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_winter_details', function (Blueprint $table): void {
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });
        Schema::table('issue_reports', function (Blueprint $table): void {
            $table->dateTime('assigned_at')->nullable()->change();
            $table->dateTime('resolved_at')->nullable()->change();
        });
        Schema::table('logs', fn (Blueprint $table) => $table->dateTime('date')->useCurrent()->useCurrentOnUpdate()->change());
        Schema::table('user_notifications', fn (Blueprint $table) => $table->dateTime('created_at')->useCurrent()->change());
    }

    public function down(): void
    {
        Schema::table('booking_winter_details', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });
        Schema::table('issue_reports', function (Blueprint $table): void {
            $table->timestamp('assigned_at')->nullable()->change();
            $table->timestamp('resolved_at')->nullable()->change();
        });
        Schema::table('logs', fn (Blueprint $table) => $table->timestamp('date')->useCurrent()->useCurrentOnUpdate()->change());
        Schema::table('user_notifications', fn (Blueprint $table) => $table->timestamp('created_at')->useCurrent()->change());
    }
};
