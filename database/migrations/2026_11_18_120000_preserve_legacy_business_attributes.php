<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cards', 'equips', 'locations', 'bonus', 'service_slots'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->boolean('active')->nullable()->default(true);
            });
        }

        foreach (['services', 'bonus'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->string('legacy_title_key', 100)->nullable();
            });
        }

        Schema::table('cards', fn (Blueprint $table) => $table->string('title', 120)->default('')->change());
        Schema::table('equips', fn (Blueprint $table) => $table->string('title', 120)->default('')->change());
        Schema::table('skills', fn (Blueprint $table) => $table->string('title', 120)->change());

        Schema::table('booking_winter_details', function (Blueprint $table): void {
            $table->boolean('equip_enabled')->nullable();
        });

        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->string('legacy_link_path', 255)->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('legacy_mutation_receipts', function (Blueprint $table): void {
            $table->string('operation', 32);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->char('request_key', 32);
            $table->char('request_hash', 64);
            $table->json('result_json')->nullable();
            $table->timestamp('created_at');
            $table->primary(['operation', 'actor_id', 'request_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_mutation_receipts');
        Schema::table('user_notifications', fn (Blueprint $table) => $table->dropColumn(['legacy_link_path', 'updated_at']));
        Schema::table('booking_winter_details', fn (Blueprint $table) => $table->dropColumn('equip_enabled'));
        Schema::table('skills', fn (Blueprint $table) => $table->string('title', 30)->change());
        Schema::table('equips', fn (Blueprint $table) => $table->string('title', 100)->default('')->change());
        Schema::table('cards', fn (Blueprint $table) => $table->string('title', 100)->default('')->change());

        foreach (['services', 'bonus'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('legacy_title_key'));
        }

        foreach (['cards', 'equips', 'locations', 'bonus', 'service_slots'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('active'));
        }
    }
};
