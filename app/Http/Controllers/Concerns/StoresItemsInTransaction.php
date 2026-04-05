<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait StoresItemsInTransaction
{
    /**
     * @template TModel of Model
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>|null  $items
     * @param  callable(array<string, mixed>): TModel  $creator
     * @param  callable(TModel): void  $assignDocumentNumber
     * @param  callable(TModel, array<int, array<string, mixed>>): void  $syncItems
     * @return TModel
     */
    protected function storeWithSyncedItems(
        array $attributes,
        ?array $items,
        callable $creator,
        callable $assignDocumentNumber,
        callable $syncItems
    ): Model {
        /** @var TModel $model */
        $model = DB::transaction(function () use ($attributes, $items, $creator, $assignDocumentNumber, $syncItems) {
            $model = $creator($attributes);

            $assignDocumentNumber($model);

            if (is_array($items)) {
                $syncItems($model, $items);
            }

            return $model;
        });

        return $model;
    }

    protected function assignSequentialDocumentNumber(Model $model, string $attribute, string $prefix): void
    {
        if (! empty($model->getAttribute($attribute))) {
            return;
        }

        $model->update([
            $attribute => $prefix
                . '-'
                . now()->format('Y')
                . '-'
                . str_pad((string) $model->getKey(), 6, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>|null  $items
     * @param  callable(array<string, mixed>): array<string, mixed>  $payloadResolver
     * @param  callable(TModel, array<int, array<string, mixed>>): void  $syncItems
     */
    protected function updateWithSyncedItems(
        Model $model,
        array $attributes,
        ?array $items,
        callable $payloadResolver,
        callable $syncItems
    ): void {
        $payload = $payloadResolver($attributes);

        DB::transaction(function () use ($model, $payload, $items, $syncItems): void {
            $model->update($payload);

            if (is_array($items)) {
                $syncItems($model, $items);
            }
        });
    }
}
