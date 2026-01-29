<?php

namespace App\Actions\Units;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\Unit;

class IndexUnitsAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }
}
