<?php

use App\Exports\CustomerCategoryExport;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer_categories');

describe('CustomerCategoryExport', function () {
    test('query applies search filter case-insensitively', function () {
        CustomerCategory::factory()->create(['name' => 'Engineering Category']);
        CustomerCategory::factory()->create(['name' => 'Marketing Category']);
        CustomerCategory::factory()->create(['name' => 'Sales Category']);

        $export = new CustomerCategoryExport(['search' => 'ENG']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering Category');
    });

    test('query applies exact name filter', function () {
        CustomerCategory::factory()->create(['name' => 'Engineering']);
        CustomerCategory::factory()->create(['name' => 'Marketing']);
        CustomerCategory::factory()->create(['name' => 'Sales']);

        $export = new CustomerCategoryExport(['name' => 'Engineering']);

        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Engineering');
    });

    test('query applies ascending sort by name', function () {
        CustomerCategory::factory()->create(['name' => 'Zeta Category']);
        CustomerCategory::factory()->create(['name' => 'Alpha Category']);
        CustomerCategory::factory()->create(['name' => 'Beta Category']);

        $export = new CustomerCategoryExport(['sort_by' => 'name', 'sort_direction' => 'asc']);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('Alpha Category')
            ->and($results[1]->name)->toBe('Beta Category')
            ->and($results[2]->name)->toBe('Zeta Category');
    });

    test('query applies descending sort by created_at when no sort specified', function () {
        $oldItem = CustomerCategory::factory()->create(['name' => 'Old Category']);
        $oldItem->created_at = now()->subDays(2);
        $oldItem->save();

        $newItem = CustomerCategory::factory()->create(['name' => 'New Category']);
        $newItem->created_at = now();
        $newItem->save();

        $export = new CustomerCategoryExport([]);

        $results = $export->query()->get();

        expect($results[0]->name)->toBe('New Category')
            ->and($results[1]->name)->toBe('Old Category');
    });

    test('query does not allow invalid sort columns', function () {
        CustomerCategory::factory()->create(['name' => 'Test Category']);

        $export = new CustomerCategoryExport(['sort_by' => 'invalid_column']);

        // Should not throw error, just ignore invalid sort
        $results = $export->query()->get();

        expect($results)->toHaveCount(1);
    });

    test('query combines search and sorting', function () {
        CustomerCategory::factory()->create(['name' => 'Zeta Engineering']);
        CustomerCategory::factory()->create(['name' => 'Alpha Engineering']);
        CustomerCategory::factory()->create(['name' => 'Marketing']);

        $export = new CustomerCategoryExport([
            'search' => 'engineering',
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(2)
            ->and($results->first()->name)->toBe('Alpha Engineering')
            ->and($results->last()->name)->toBe('Zeta Engineering');
    });

    test('headings returns correct column headers', function () {
        $export = new CustomerCategoryExport([]);

        $headings = $export->headings();

        expect($headings)->toBe([
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ]);
    });

    test('map transforms data correctly with timestamps', function () {
        $item = CustomerCategory::factory()->create([
            'name' => 'Engineering Category',
            'created_at' => '2023-01-15 14:30:00',
            'updated_at' => '2023-01-20 09:15:00',
        ]);

        $export = new CustomerCategoryExport([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Engineering Category',
            '2023-01-15 14:30:00',
            '2023-01-20 09:15:00',
        ]);
    });

    test('map handles null timestamps gracefully', function () {
        $item = CustomerCategory::factory()->create([
            'name' => 'Test Category',
            'created_at' => null,
            'updated_at' => null,
        ]);

        $export = new CustomerCategoryExport([]);
        $mapped = $export->map($item);

        expect($mapped)->toBe([
            $item->id,
            'Test Category',
            null,
            null,
        ]);
    });

    test('map handles carbon timestamp objects', function () {
        $item = CustomerCategory::factory()->create([
            'name' => 'Carbon Test Category',
        ]);

        // Ensure timestamps are Carbon instances
        expect($item->created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        $export = new CustomerCategoryExport([]);
        $mapped = $export->map($item);

        expect($mapped[0])->toBe($item->id)
            ->and($mapped[1])->toBe('Carbon Test Category')
            ->and($mapped[2])->toBeString()
            ->and($mapped[3])->toBeString();
    });

    test('handles empty filters gracefully', function () {
        CustomerCategory::factory()->count(3)->create();

        $export = new CustomerCategoryExport([]);

        $results = $export->query()->get();

        expect($results)->toHaveCount(3);
    });
});
