<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Unit\Traits\HandlesConditionsHarness;

uses(RefreshDatabase::class)->group('handles-conditions');

beforeEach(function () {
    $this->harness = new HandlesConditionsHarness;
});

test('evaluateConditions returns true when conditions are empty', function () {
    $asset = Asset::factory()->make(['status' => 'in_use']);

    expect($this->harness->runEvaluateConditions([], $asset))->toBeTrue();
});

test('evaluateConditions returns false when any field_check fails', function () {
    $asset = Asset::factory()->make(['status' => 'in_use']);

    $result = $this->harness->runEvaluateConditions([
        'field_checks' => [
            ['field' => 'status', 'operator' => 'equals', 'value' => 'retired'],
        ],
    ], $asset);

    expect($result)->toBeFalse();
});

test('evaluateConditions returns true when all field_checks pass', function () {
    $asset = Asset::factory()->make(['status' => 'in_use', 'name' => 'Laptop']);

    $result = $this->harness->runEvaluateConditions([
        'field_checks' => [
            ['field' => 'status', 'operator' => 'equals', 'value' => 'in_use'],
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Lap'],
        ],
    ], $asset);

    expect($result)->toBeTrue();
});

test('evaluateConditions returns false when any relation_check fails', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    $result = $this->harness->runEvaluateConditions([
        'relation_checks' => [
            ['relation' => 'category', 'field' => 'code', 'operator' => 'equals', 'value' => 'HR'],
        ],
    ], $asset);

    expect($result)->toBeFalse();
});

test('evaluateConditions returns true when all relation_checks pass', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    $result = $this->harness->runEvaluateConditions([
        'relation_checks' => [
            ['relation' => 'category', 'field' => 'code', 'operator' => 'equals', 'value' => 'IT'],
        ],
    ], $asset);

    expect($result)->toBeTrue();
});

test('evaluateFieldCheck returns true when field key is missing', function () {
    $asset = Asset::factory()->make();

    expect($this->harness->runEvaluateFieldCheck(['operator' => 'equals', 'value' => 'x'], $asset))->toBeTrue();
});

test('evaluateFieldCheck equals and = operators', function () {
    $asset = Asset::factory()->make(['status' => 'active']);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => '=', 'value' => 'active'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'equals', 'value' => 'draft'],
        $asset,
    ))->toBeFalse();
});

test('evaluateFieldCheck not_equals and != operators', function () {
    $asset = Asset::factory()->make(['status' => 'active']);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'not_equals', 'value' => 'draft'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => '!=', 'value' => 'active'],
        $asset,
    ))->toBeFalse();
});

test('evaluateFieldCheck comparison operators', function () {
    $asset = Asset::factory()->make(['purchase_cost' => 1000]);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => 'greater_than', 'value' => 500],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => '>', 'value' => 2000],
        $asset,
    ))->toBeFalse();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => 'less_than', 'value' => 2000],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => '<', 'value' => 500],
        $asset,
    ))->toBeFalse();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => 'greater_than_or_equal', 'value' => 1000],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => '>=', 'value' => 1001],
        $asset,
    ))->toBeFalse();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => 'less_than_or_equal', 'value' => 1000],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'purchase_cost', 'operator' => '<=', 'value' => 999],
        $asset,
    ))->toBeFalse();
});

test('evaluateFieldCheck contains operator', function () {
    $asset = Asset::factory()->make(['name' => 'Dell Latitude 5520']);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'name', 'operator' => 'contains', 'value' => 'Latitude'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'name', 'operator' => 'contains', 'value' => 'MacBook'],
        $asset,
    ))->toBeFalse();
});

test('evaluateFieldCheck not_null and is_null operators', function () {
    $withName = Asset::factory()->make(['name' => 'Present']);
    $nullName = Asset::factory()->make(['name' => null]);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'name', 'operator' => 'not_null'],
        $withName,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'name', 'operator' => 'not_null'],
        $nullName,
    ))->toBeFalse();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'name', 'operator' => 'is_null'],
        $nullName,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'name', 'operator' => 'is_null'],
        $withName,
    ))->toBeFalse();
});

test('evaluateFieldCheck in and not_in operators with array and scalar', function () {
    $asset = Asset::factory()->make(['status' => 'in_use']);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'in', 'value' => ['in_use', 'stored']],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'in', 'value' => ['retired', 'disposed']],
        $asset,
    ))->toBeFalse();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'in', 'value' => 'in_use'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'not_in', 'value' => ['retired', 'disposed']],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'not_in', 'value' => ['in_use', 'stored']],
        $asset,
    ))->toBeFalse();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'not_in', 'value' => 'retired'],
        $asset,
    ))->toBeTrue();
});

test('evaluateFieldCheck defaults operator to equals and unknown operator to false', function () {
    $asset = Asset::factory()->make(['status' => 'active']);

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'value' => 'active'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateFieldCheck(
        ['field' => 'status', 'operator' => 'unknown_op', 'value' => 'active'],
        $asset,
    ))->toBeFalse();
});

test('evaluateRelationCheck returns true when relation or field is missing', function () {
    $asset = Asset::factory()->make();

    expect($this->harness->runEvaluateRelationCheck(
        ['field' => 'code', 'operator' => 'equals', 'value' => 'IT'],
        $asset,
    ))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck(
        ['relation' => 'category', 'operator' => 'equals', 'value' => 'IT'],
        $asset,
    ))->toBeTrue();
});

test('evaluateRelationCheck returns false when related model is null', function () {
    $asset = Asset::factory()->make();
    $asset->setRelation('category', null);

    $result = $this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'equals',
        'value' => 'IT',
    ], $asset);

    expect($result)->toBeFalse();
});

test('evaluateRelationCheck equals and not_equals operators', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'equals',
        'value' => 'IT',
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'not_equals',
        'value' => 'HR',
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => '!=',
        'value' => 'IT',
    ], $asset))->toBeFalse();
});

test('evaluateRelationCheck comparison operators', function () {
    $category = AssetCategory::factory()->create([
        'code' => 'IT',
        'useful_life_months_default' => 36,
    ]);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => 'greater_than',
        'value' => 12,
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => 'less_than',
        'value' => 12,
    ], $asset))->toBeFalse();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => 'greater_than_or_equal',
        'value' => 36,
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => 'less_than_or_equal',
        'value' => 36,
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => '>',
        'value' => 100,
    ], $asset))->toBeFalse();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => '<',
        'value' => 100,
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => '>=',
        'value' => 37,
    ], $asset))->toBeFalse();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'useful_life_months_default',
        'operator' => '<=',
        'value' => 35,
    ], $asset))->toBeFalse();
});

test('evaluateRelationCheck not_null and is_null operators', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'not_null',
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'is_null',
    ], $asset))->toBeFalse();
});

test('evaluateRelationCheck in and not_in operators with array and scalar', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'in',
        'value' => ['IT', 'HR'],
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'in',
        'value' => ['HR', 'FIN'],
    ], $asset))->toBeFalse();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'in',
        'value' => 'IT',
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'not_in',
        'value' => ['HR', 'FIN'],
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'not_in',
        'value' => ['IT', 'HR'],
    ], $asset))->toBeFalse();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'not_in',
        'value' => 'HR',
    ], $asset))->toBeTrue();
});

test('evaluateRelationCheck defaults operator to equals and unknown operator to false', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'value' => 'IT',
    ], $asset))->toBeTrue();

    expect($this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'bogus',
        'value' => 'IT',
    ], $asset))->toBeFalse();
});

test('evaluateRelationCheck lazy-loads relation when not already loaded', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);
    $fresh = Asset::find($asset->id);

    expect($fresh->relationLoaded('category'))->toBeFalse();

    $this->harness->runEvaluateRelationCheck([
        'relation' => 'category',
        'field' => 'code',
        'operator' => 'equals',
        'value' => 'IT',
    ], $fresh);

    expect($fresh->relationLoaded('category'))->toBeTrue();
});
