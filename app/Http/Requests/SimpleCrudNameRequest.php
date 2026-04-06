<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;
use LogicException;

abstract class SimpleCrudNameRequest extends AuthorizedFormRequest
{
    protected function modelClass(): string
    {
        return $this->getModelClass();
    }

    protected function modelTable(): string
    {
        $modelClass = $this->modelClass();
        $model = new $modelClass;

        return $model->getTable();
    }

    protected function routeResourceName(): string
    {
        return Str::snake(class_basename($this->modelClass()));
    }

    protected function routeResourceId(): mixed
    {
        return $this->route($this->routeResourceName())->id ?? $this->route('id');
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected function getModelClass(): string
    {
        $requestClass = static::class;

        if (str_starts_with(class_basename($requestClass), 'Mockery_')) {
            $parentClass = get_parent_class($requestClass);

            if ($parentClass !== false) {
                $requestClass = $parentClass;
            }
        }

        $modelClass = 'App\\Models\\' . str_replace(
            ['Store', 'Update', 'Request'],
            '',
            class_basename($requestClass),
        );

        if (! class_exists($modelClass)) {
            throw new LogicException('Unable to infer model class for request [' . static::class . '].');
        }

        return $modelClass;
    }
}
