<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;

trait LoadsResourceRelations
{
    abstract protected function resourceRelations(): array;

    protected function loadResourceRelations(Model $model): Model
    {
        return $model->load($this->resourceRelations());
    }
}
