<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bonus;
use App\Models\Card;
use App\Models\Equip;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceSlot;
use Database\Seeders\Support\SeedCatalog;
use Illuminate\Database\Seeder;

class NormalizeLegacyLocalizedDataSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertDevelopmentDatabase();

        $this->normalizeServices();
        $this->normalizeBonus();
        $this->normalizeCards();
        $this->normalizeEquips();
        $this->normalizeRoles();
        $this->normalizeServiceSlots();
    }

    private function normalizeServices(): void
    {
        foreach (SeedCatalog::serviceTitles() as $id => $title) {
            Service::query()
                ->whereKey($id)
                ->update(SeedCatalog::localizedTitlePayload($title));
        }
    }

    private function normalizeBonus(): void
    {
        foreach (SeedCatalog::bonusTitles() as $id => $title) {
            Bonus::query()
                ->whereKey($id)
                ->update(SeedCatalog::localizedTitlePayload($title));
        }
    }

    private function normalizeCards(): void
    {
        foreach (SeedCatalog::cardTitles() as $id => $title) {
            Card::query()
                ->whereKey($id)
                ->update(SeedCatalog::localizedTitlePayload($title));
        }
    }

    private function normalizeEquips(): void
    {
        foreach (SeedCatalog::equipTitles() as $id => $title) {
            Equip::query()
                ->whereKey($id)
                ->update(SeedCatalog::localizedTitlePayload($title));
        }
    }

    private function normalizeRoles(): void
    {
        foreach (SeedCatalog::roleTitles() as $roleKey => $title) {
            Role::query()
                ->where('role_key', $roleKey)
                ->update([
                    'title_bg' => $title,
                    'title_en' => $title,
                    'title_translations' => json_encode(SeedCatalog::translations($title), JSON_THROW_ON_ERROR),
                ]);
        }
    }

    private function normalizeServiceSlots(): void
    {
        ServiceSlot::query()
            ->with('service:id,title,title_en,title_bg,title_translations')
            ->get()
            ->each(function (ServiceSlot $slot): void {
                $service = $slot->service;
                if (! $service instanceof Service) {
                    return;
                }

                $serviceTitle = SeedCatalog::serviceTitles()[(int) $service->id]
                    ?? $this->resolveFallbackTitle($service);

                $start = $slot->getRawOriginal('date_start');
                $end = $slot->getRawOriginal('date_end');
                $range = $this->yearRange(is_string($start) ? $start : null, is_string($end) ? $end : null);

                $currentName = trim((string) $slot->name);

                if (
                    $currentName !== ''
                    && ! $this->containsCyrillic($currentName)
                    && ! preg_match('/^(?:Season\\s+)?\\d{4}(?:\\/\\d{4})?$/i', $currentName)
                ) {
                    return;
                }

                $slot->forceFill([
                    'name' => trim($serviceTitle.' '.$range),
                ])->save();
            });
    }

    private function resolveFallbackTitle(Service $service): string
    {
        $fallback = trim((string) ($service->title_en ?: $service->title ?: 'Season'));

        return $fallback !== '' ? $fallback : 'Season';
    }

    private function yearRange(?string $startDate, ?string $endDate): string
    {
        if ($startDate !== null && $endDate !== null) {
            $startYear = (int) substr($startDate, 0, 4);
            $endYear = (int) substr($endDate, 0, 4);

            return $startYear === $endYear
                ? (string) $startYear
                : sprintf('%d/%d', $startYear, $endYear);
        }

        if ($startDate !== null) {
            return substr($startDate, 0, 4);
        }

        return 'Season';
    }

    private function containsCyrillic(string $value): bool
    {
        return preg_match('/\p{Cyrillic}/u', $value) === 1;
    }
}
