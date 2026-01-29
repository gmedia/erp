<?php

namespace Tests\Feature;

use App\Exports\UnitExport;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class UnitExportTest extends TestCase
{
    use RefreshDatabase, SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return UnitExport::class;
    }

    protected function getModelClass(): string
    {
        return Unit::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Kilogram',
            'others' => ['Meter', 'Liter'],
        ];
    }
}
