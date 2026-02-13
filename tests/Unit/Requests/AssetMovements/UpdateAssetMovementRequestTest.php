<?php

namespace Tests\Unit\Requests\AssetMovements;

use App\Http\Requests\AssetMovements\UpdateAssetMovementRequest;
use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class)->group('asset-movements');

function createUpdateRequest($model, array $data = []): UpdateAssetMovementRequest
{
    $request = new UpdateAssetMovementRequest();
    $request->merge($data);
    $request->setRouteResolver(function () use ($model) {
        return new class($model) {
            private $model;
            public function __construct($model) { $this->model = $model; }
            public function parameter($name) { return $this->model; }
        };
    });
    return $request;
}

function assertUpdateValidationPasses($data, $model = null)
{
    if (!$model) {
        $model = AssetMovement::factory()->create();
    }
    $request = createUpdateRequest($model, $data);
    $validator = validator($data, $request->rules());
    expect($validator->fails())->toBeFalse('Validation should have passed but failed: ' . implode(', ', $validator->errors()->all()));
}

test('it authorizes request', function () {
    $model = AssetMovement::factory()->create();
    $request = createUpdateRequest($model);
    expect($request->authorize())->toBeTrue();
});

test('it validates update rules', function () {
    // Update request logic allows updating notes and reference
    $data = [
        'moved_at' => '2023-01-01', // Required field
        'notes' => 'Updated notes',
        'reference' => 'REF-UPDATED',
    ];

    assertUpdateValidationPasses($data);
});
