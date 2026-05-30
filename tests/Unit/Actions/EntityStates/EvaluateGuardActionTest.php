<?php

use App\Actions\EntityStates\EvaluateGuardAction;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\PipelineTransition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Unit\Actions\EntityStates\Fixtures\GuardAlwaysFailRule;
use Tests\Unit\Actions\EntityStates\Fixtures\GuardAlwaysPassRule;
use Tests\Unit\Actions\EntityStates\Fixtures\GuardThrowingRule;

uses(RefreshDatabase::class)->group('entity-state-actions');

beforeEach(function () {
    $this->action = new EvaluateGuardAction;
});

test('returns empty array when no guards configured', function () {
    $transition = PipelineTransition::factory()->make(['guard_conditions' => []]);
    $asset = Asset::factory()->make(['status' => 'in_use']);

    expect($this->action->execute($transition, $asset))->toBe([]);
});

test('field_checks pass when all conditions match', function () {
    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => [
            'field_checks' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'in_use'],
            ],
        ],
    ]);
    $asset = Asset::factory()->make(['status' => 'in_use']);

    expect($this->action->execute($transition, $asset))->toBe([]);
});

test('field_checks return failure when condition does not match', function () {
    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => [
            'field_checks' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'retired'],
            ],
        ],
    ]);
    $asset = Asset::factory()->make(['status' => 'in_use']);

    $failures = $this->action->execute($transition, $asset);

    expect($failures)->toHaveCount(1);
    expect($failures[0])->toContain('Field check failed: status must equals')
        ->toContain("'retired'")
        ->toContain("'in_use'");
});

test('field_checks evaluate multiple conditions and accumulate failures', function () {
    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => [
            'field_checks' => [
                ['field' => 'status', 'operator' => 'equals', 'value' => 'retired'],
                ['field' => 'name', 'operator' => 'equals', 'value' => 'NotMe'],
            ],
        ],
    ]);
    $asset = Asset::factory()->make([
        'status' => 'in_use',
        'name' => 'My Laptop',
    ]);

    $failures = $this->action->execute($transition, $asset);

    expect($failures)->toHaveCount(2);
});

test('relation_checks pass when related model field matches', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => [
            'relation_checks' => [
                ['relation' => 'category', 'field' => 'code', 'operator' => 'equals', 'value' => 'IT'],
            ],
        ],
    ]);

    expect($this->action->execute($transition, $asset))->toBe([]);
});

test('relation_checks return failure with current value when relation field mismatches', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);

    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => [
            'relation_checks' => [
                ['relation' => 'category', 'field' => 'code', 'operator' => 'equals', 'value' => 'HR'],
            ],
        ],
    ]);

    $failures = $this->action->execute($transition, $asset);

    expect($failures)->toHaveCount(1);
    expect($failures[0])->toContain('Relation check failed: category.code must equals')
        ->toContain("'HR'")
        ->toContain("'IT'");
});

test('relation_checks lazy-load the relation when not yet loaded', function () {
    $category = AssetCategory::factory()->create(['code' => 'IT']);
    $asset = Asset::factory()->create(['asset_category_id' => $category->id]);
    $fresh = Asset::find($asset->id);

    expect($fresh->relationLoaded('category'))->toBeFalse();

    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => [
            'relation_checks' => [
                ['relation' => 'category', 'field' => 'code', 'operator' => 'equals', 'value' => 'OTHER'],
            ],
        ],
    ]);

    $this->action->execute($transition, $fresh);

    expect($fresh->relationLoaded('category'))->toBeTrue();
});

test('custom_rule passes silently when evaluate returns true', function () {
    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => ['custom_rule' => GuardAlwaysPassRule::class],
    ]);
    $asset = Asset::factory()->make();

    expect($this->action->execute($transition, $asset))->toBe([]);
});

test('custom_rule reports failure when evaluate returns false', function () {
    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => ['custom_rule' => GuardAlwaysFailRule::class],
    ]);
    $asset = Asset::factory()->make();

    $failures = $this->action->execute($transition, $asset);

    expect($failures)->toHaveCount(1);
    expect($failures[0])->toContain('Custom rule')->toContain(GuardAlwaysFailRule::class);
});

test('custom_rule reports execution failure when rule throws and logs error', function () {
    Log::spy();

    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => ['custom_rule' => GuardThrowingRule::class],
    ]);
    $asset = Asset::factory()->make();

    $failures = $this->action->execute($transition, $asset);

    expect($failures)->toHaveCount(1);
    expect($failures[0])->toContain('Custom rule execution failed')
        ->toContain(GuardThrowingRule::class);

    Log::shouldHaveReceived('error')->once();
});

test('custom_rule reports class-not-found when class string does not exist', function () {
    $transition = PipelineTransition::factory()->make([
        'guard_conditions' => ['custom_rule' => 'App\\NonExistent\\GuardRule'],
    ]);
    $asset = Asset::factory()->make();

    $failures = $this->action->execute($transition, $asset);

    expect($failures)->toHaveCount(1);
    expect($failures[0])->toContain('Custom rule class not found');
});
