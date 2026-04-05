<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class SimpleCrudNameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
    abstract protected function getModelClass(): string;
}
