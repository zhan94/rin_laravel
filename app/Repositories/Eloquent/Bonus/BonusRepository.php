<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Bonus;

use App\DTOs\Bonus\BonusData;
use App\DTOs\Bonus\BonusFilterData;
use App\DTOs\Bonus\BonusPriceData;
use App\Models\Bonus;
use App\Repositories\Contracts\Bonus\BonusRepositoryInterface;
use App\Support\Localization\LocalizedText;
use App\Support\Pricing\PriceRowSynchronizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BonusRepository implements BonusRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Bonus>
     */
    public function paginate(BonusFilterData $filters): LengthAwarePaginator
    {
        return Bonus::query()
            ->with('prices')
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('title', 'like', $q)
                        ->orWhere('location_from', 'like', $q)
                        ->orWhere('location_to', 'like', $q);

                    foreach (LocalizedText::supportedLocales() as $locale) {
                        $nested->orWhere("title_translations->{$locale}", 'like', $q);
                    }

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function create(BonusData $data): Bonus
    {
        $bonus = Bonus::query()->create($data->toPersistence());
        $this->replacePrices($bonus, $data);

        return $bonus->load('prices');
    }

    public function update(Bonus $bonus, BonusData $data): Bonus
    {
        $bonus->fill($data->toPersistence());
        $bonus->save();

        $this->replacePrices($bonus, $data);

        return $bonus->load('prices');
    }

    public function delete(Bonus $bonus): void
    {
        $bonus->delete();
    }

    public function findWithRelations(int $id): ?Bonus
    {
        return Bonus::query()->with('prices')->find($id);
    }

    private function replacePrices(Bonus $bonus, BonusData $data): void
    {
        $bonusId = (int) $bonus->id;
        $foreignKey = Bonus::resolvePriceForeignKey();

        PriceRowSynchronizer::replace(
            $bonus->prices(),
            $data->prices,
            fn (BonusPriceData $price): array => $price->toPersistence($foreignKey, $bonusId),
        );
    }
}
