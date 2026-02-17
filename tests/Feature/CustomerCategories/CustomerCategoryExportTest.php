<?php

use App\Exports\CustomerCategoryExport;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer-categories');

describe('CustomerCategoryExport', function () {

    test('query applies search filter', function () {
        CustomerCategory::factory()->create(['name' => 'VIP']);
        CustomerCategory::factory()->create(['name' => 'Regular']);

        $export = new CustomerCategoryExport(['search' => 'VIP']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('VIP');
    });

    test('map function returns correct data', function () {
        $category = CustomerCategory::factory()->make([
            'id' => 1,
            'name' => 'Test Cat',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new CustomerCategoryExport([]);
        $mapped = $export->map($category);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Cat');
    });

    test('headings returns correct columns', function () {
        $export = new CustomerCategoryExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
