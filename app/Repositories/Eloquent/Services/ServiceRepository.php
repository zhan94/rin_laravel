<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Services;

use App\DTOs\Services\ServiceData;
use App\DTOs\Services\ServiceFilterData;
use App\Models\Service;
use App\Repositories\Contracts\Services\ServiceRepositoryInterface;
use App\Support\Localization\LocalizedText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ServiceRepository implements ServiceRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginate(ServiceFilterData $filters): LengthAwarePaginator
    {
        return Service::query()
            ->withCount('slots')
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('title', 'like', $q);

                    foreach (LocalizedText::supportedLocales() as $locale) {
                        $nested->orWhere("title_translations->{$locale}", 'like', $q);
                    }

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->when($filters->active !== null, fn ($query) => $query->where('active', $filters->active))
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function create(ServiceData $data): Service
    {
        return Service::query()->create($data->toPersistence());
    }

    public function update(Service $service, ServiceData $data): Service
    {
        $service->fill($data->toPersistence());
        $service->save();

        return $service;
    }

    public function deactivate(Service $service): Service
    {
        $service->active = false;
        $service->save();

        return $service;
    }
}
