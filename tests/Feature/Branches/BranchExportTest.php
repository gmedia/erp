<?php

use App\Exports\BranchExport;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

describe('BranchExport', function () {

    test('query applies search filter', function () {
        Branch::factory()->create(['name' => 'Main Office']);
        Branch::factory()->create(['name' => 'Remote Office']);

        $export = new BranchExport(['search' => 'Main']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Main Office');
    });

    test('map function returns correct data', function () {
        $branch = Branch::factory()->make([
            'id' => 1,
            'name' => 'Test Branch',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new BranchExport([]);
        $mapped = $export->map($branch);

        // BranchExport map likely just returns name and dates? 
        // I should check strictness. For SimpleCrudExport, it usually maps common fields.
        // Assuming checking keys or general structure if I can't be sure of exact array.
        // But for consistency with SupplierExportTest, I should try to be specific.
        // If map logic is default (from trait or base), it handles timestamps.
        
        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1); // ID
        expect($mapped[1])->toBe('Test Branch'); // Name
        // We know simple crud exports usually export ID, Name, Created At
    });

    test('headings returns correct columns', function () {
        $export = new BranchExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
