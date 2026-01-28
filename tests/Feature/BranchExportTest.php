<?php

namespace Tests\Feature;

use App\Exports\BranchExport;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudExportTestTrait;

class BranchExportTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudExportTestTrait;

    protected function getExportClass(): string
    {
        return BranchExport::class;
    }

    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getSampleData(): array
    {
        return [
            'match' => 'Jakarta Branch',
            'others' => ['Surabaya Branch', 'Bandung Branch'],
        ];
    }
}
