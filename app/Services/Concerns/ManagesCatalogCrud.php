<?php

declare(strict_types=1);

namespace App\Services\Concerns;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\ManagerActionNotificationService;
use App\Support\Catalog\CatalogCrudDescriptor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait ManagesCatalogCrud
{
    /**
     * @template TEntity of Model
     * @template TData
     *
     * @param  TData  $data
     * @param  callable(TData): TEntity  $create
     * @return TEntity
     */
    protected function createCatalogEntity(mixed $data, ?User $actor, callable $create): Model
    {
        /** @var TEntity $created */
        $created = DB::transaction(function () use ($data, $actor, $create): Model {
            $created = $create($data);

            if ($actor instanceof User) {
                $this->notifyCatalogEntityAction($actor, 'created', $created);
            }

            return $created;
        });

        return $created;
    }

    /**
     * @template TEntity of Model
     *
     * @param  callable(int): (TEntity|null)  $find
     * @return TEntity
     */
    protected function findCatalogEntityOrFail(int $id, callable $find): Model
    {
        $entity = $find($id);
        if ($entity !== null) {
            return $entity;
        }

        $descriptor = $this->catalogCrudDescriptor();

        throw new ApiException(
            errorCode: $descriptor->notFoundErrorCode,
            message: $descriptor->notFoundMessage,
            statusCode: 404,
        );
    }

    /**
     * @template TEntity of Model
     * @template TData
     *
     * @param  TEntity  $entity
     * @param  TData  $data
     * @param  callable(TEntity, TData): TEntity  $update
     * @return TEntity
     */
    protected function updateCatalogEntity(Model $entity, mixed $data, ?User $actor, callable $update): Model
    {
        /** @var TEntity $updated */
        $updated = DB::transaction(function () use ($entity, $data, $actor, $update): Model {
            $updated = $update($entity, $data);

            if ($actor instanceof User) {
                $this->notifyCatalogEntityAction($actor, 'updated', $updated);
            }

            return $updated;
        });

        return $updated;
    }

    /**
     * @template TEntity of Model
     *
     * @param  TEntity  $entity
     * @param  callable(TEntity): void  $delete
     */
    protected function deleteCatalogEntity(Model $entity, ?User $actor, callable $delete): void
    {
        DB::transaction(function () use ($entity, $actor, $delete): void {
            $delete($entity);

            if ($actor instanceof User) {
                $this->notifyCatalogEntityAction($actor, 'deleted', $entity);
            }
        });
    }

    abstract protected function catalogCrudDescriptor(): CatalogCrudDescriptor;

    abstract protected function catalogActionNotifier(): ManagerActionNotificationService;

    private function notifyCatalogEntityAction(User $actor, string $actionKey, Model $entity): void
    {
        $descriptor = $this->catalogCrudDescriptor();

        $this->catalogActionNotifier()->notifyAdminsForManagerEntityAction(
            actor: $actor,
            moduleKey: $descriptor->moduleKey,
            actionKey: $actionKey,
            actionText: $actionKey,
            titleEntityLabel: $descriptor->titleEntityLabel,
            messageEntityLabel: $descriptor->messageEntityLabel,
            label: $this->catalogEntityLabel($entity),
            entityType: $descriptor->entityType,
            entityId: max(0, (int) $entity->getKey()),
            linkPath: $descriptor->linkPath,
        );
    }

    private function catalogEntityLabel(Model $entity): string
    {
        if (method_exists($entity, 'displayTitleOrId')) {
            $label = trim((string) $entity->displayTitleOrId());
            if ($label !== '') {
                return $label;
            }
        }

        $key = $entity->getKey();

        return $key !== null ? sprintf('%s #%s', class_basename($entity), (string) $key) : class_basename($entity);
    }
}
