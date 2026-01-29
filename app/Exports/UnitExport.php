<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\Unit;

class UnitExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }
}
