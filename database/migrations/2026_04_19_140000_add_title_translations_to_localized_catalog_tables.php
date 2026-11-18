<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int,string>
     */
    private array $tables = [
        'services',
        'cards',
        'equips',
        'bonus',
        'roles',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $afterColumn = Schema::hasColumn($table, 'title')
                ? 'title'
                : (Schema::hasColumn($table, 'title_en') ? 'title_en' : 'role_key');

            Schema::table($table, function (Blueprint $table) use ($afterColumn): void {
                if (! Schema::hasColumn($table->getTable(), 'title_translations')) {
                    $table->json('title_translations')->nullable()->after($afterColumn);
                }
            });
        }

        $this->backfillTranslations();
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                if (Schema::hasColumn($table->getTable(), 'title_translations')) {
                    $table->dropColumn('title_translations');
                }
            });
        }
    }

    private function backfillTranslations(): void
    {
        foreach ($this->tables as $table) {
            $columns = ['id'];

            if (Schema::hasColumn($table, 'title')) {
                $columns[] = 'title';
            }

            if (Schema::hasColumn($table, 'title_bg')) {
                $columns[] = 'title_bg';
            }

            if (Schema::hasColumn($table, 'title_en')) {
                $columns[] = 'title_en';
            }

            DB::table($table)
                ->select($columns)
                ->orderBy('id')
                ->lazy()
                ->each(function (object $row) use ($table): void {
                    $fallback = trim((string) ($row->title ?? ''));
                    $bg = trim((string) ($row->title_bg ?? ''));
                    $en = trim((string) ($row->title_en ?? ''));

                    $seed = $en !== '' ? $en : ($bg !== '' ? $bg : $fallback);

                    $translations = array_filter([
                        'bg' => $bg !== '' ? $bg : ($fallback !== '' ? $fallback : $seed),
                        'en' => $en !== '' ? $en : $seed,
                        'de' => $seed,
                        'es' => $seed,
                    ], static fn (string $value): bool => trim($value) !== '');

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update([
                            'title_translations' => $translations === []
                                ? null
                                : json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        ]);
                });
        }
    }
};
