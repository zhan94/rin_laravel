<?php

declare(strict_types=1);

namespace App\Support\Pricing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PriceRowSynchronizer
{
    /**
     * @template TPrice
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  HasMany<TRelatedModel, TDeclaringModel>  $relation
     * @param  list<TPrice>  $prices
     * @param  callable(TPrice): array<string,mixed>  $toPersistence
     */
    public static function replace(HasMany $relation, array $prices, callable $toPersistence): void
    {
        $relation->delete();

        if ($prices === []) {
            return;
        }

        $rows = [];

        foreach ($prices as $price) {
            $rows[] = $toPersistence($price);
        }

        $relation->insert($rows);
    }
}
