<?php

use App\Actions\Products\IndexProductsAction;
use App\Http\Requests\Products\IndexProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->filterService = new \App\Domain\Products\ProductFilterService();
    $this->action = new IndexProductsAction($this->filterService);
});

test('it returns paginated products', function () {
    Product::factory()->count(15)->create();

    $request = new IndexProductRequest(['per_page' => 10]);
    $result = $this->action->execute($request);

    expect($result->count())->toBe(10)
        ->and($result->total())->toBe(15);
});

test('it applies search filter', function () {
    Product::factory()->create(['name' => 'Spec Alpha']);
    Product::factory()->create(['name' => 'Spec Beta']);

    $request = new IndexProductRequest(['search' => 'Alpha']);
    $result = $this->action->execute($request);

    expect($result->count())->toBe(1);
});

test('it applies sorting', function () {
    Product::factory()->create(['name' => 'B Product']);
    Product::factory()->create(['name' => 'A Product']);

    $request = new IndexProductRequest(['sort_by' => 'name', 'sort_direction' => 'asc']);
    $result = $this->action->execute($request);

    expect($result->first()->name)->toBe('A Product');

    $request = new IndexProductRequest(['sort_by' => 'name', 'sort_direction' => 'desc']);
    $result = $this->action->execute($request);

    expect($result->first()->name)->toBe('B Product');
});
