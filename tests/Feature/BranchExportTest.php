<?php

use App\Exports\BranchExport;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

describe('BranchExport', function () {
    test('query applies search filter case-insensitively', function () {
        Branch::factory()->create(['name' => 'Jakarta Branch']);
        Branch::factory()->create(['name' => 'Surabaya Branch']);
        Branch::factory()->create(['name' => 'Bandung Branch']);

        $export = new BranchExport(['search' => 'JAK']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Jakarta Branch');
    });

    test('query applies exact name filter', function () {
        Branch::factory()->create(['name' => 'Jakarta Branch']);
        Branch::factory()->create(['name' => 'Surabaya Branch']);
        Branch::factory()->create(['name' => 'Bandung Branch']);

        $export = new BranchExport(['name' => 'Jakarta']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Jakarta Branch');
    });

    test('query applies ascending sort by name', function () {
        Branch::factory()->create(['name' => 'Zeta Branch']);
        Branch::factory()->create(['name' => 'Alpha Branch']);
        Branch::factory()->create(['name' => 'Beta Branch']);

        $export = new BranchExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alpha Branch')
            ->and($results[1]->name)->toBe('Beta Branch')
            ->and($results[2]->name)->toBe('Zeta Branch');
    });

    test('query applies descending sort by created_at when no sort specified', function () {
        $oldItem = Branch::factory()->create(['name' => 'Old Branch']);
        $oldItem->created_at = now()->subDays(2);
        $oldItem->save();

        $newItem = Branch::factory()->create(['name' => 'New Branch']);

        $export = new BranchExport([]);

        $results = $export->query()->get();

        expect($results->first()->name)->toBe('New Branch')
            ->and($results->last()->name)->toBe('Old Branch');
    });

    test('query ignores invalid sort column', function () {
        Branch::factory()->count(3)->create();

        $export = new BranchExport(['sort_by' => 'invalid_column', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        // Should still return all items, just with default sort
        expect($results)->toHaveCount(3);
    });

    test('headings returns correct column headers', function () {
        $export = new BranchExport([]);

        $headings = $export->headings();

        expect($headings)->toBe([
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ]);
    });

    test('map transforms data correctly with timestamps', function () {
        $item = Branch::factory()->create([
            'name' => 'Jakarta Branch',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $export = new BranchExport([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Jakarta Branch',
            '2023-01-15 14:30:00',
            '2023-01-20 09:15:00',
        ]);
    });

    test('map handles null timestamps gracefully', function () {
        $item = Branch::factory()->create([
            'name' => 'Test Branch',
            'created_at' => null,
            'updated_at' => null,
        ]);

        $export = new BranchExport([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Test Branch',
            null,
            null,
        ]);
    });

    test('map handles carbon timestamp objects', function () {
        $item = Branch::factory()->create([
            'name' => 'Carbon Test Branch',
        ]);

        // Ensure timestamps are Carbon instances
        expect($item->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        $export = new BranchExport([]);
        $mapped = $export->map($item);

        expect($mapped[0])->toBe($item->id)
            ->and($mapped[1])->toBe('Carbon Test Branch')
            ->and($mapped[2])->toBeString()
            ->and($mapped[3])->toBeString();
    });

    test('handles empty filters gracefully', function () {
        Branch::factory()->count(3)->create();

        $export = new BranchExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(3);
    });
});
