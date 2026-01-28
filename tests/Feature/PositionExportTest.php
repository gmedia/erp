<?php

namespace Tests\Feature;

use App\Exports\PositionExport;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class PositionExportTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return PositionExport::class;
    }

    protected function getModelClass(): string
    {
        return Position::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Senior Developer',
            'others' => ['Junior Developer', 'Project Manager'],
        ];
    }
}
