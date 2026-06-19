<?php

use App\Actions\EntityStates\AssignPipelineAction;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('pipeline-dashboard');

function makeAssetPipeline(): PipelineState
{
    $pipeline = Pipeline::factory()->create([
        'entity_type' => Asset::class,
        'is_active' => true,
    ]);

    return PipelineState::factory()->create([
        'pipeline_id' => $pipeline->id,
        'type' => 'initial',
        'sort_order' => 1,
    ]);
}

it('keeps branch_id in the PipelineEntityState fillable contract', function () {
    expect((new PipelineEntityState)->getFillable())->toContain('branch_id');
});

it('populates branch_id from the entity when assigning a pipeline', function () {
    makeAssetPipeline();
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    $state = app(AssignPipelineAction::class)->execute($asset);

    expect($state)->not->toBeNull()
        ->and($state->branch_id)->toBe($branch->id);
});

it('backfills branch_id from a direct-branch entity', function () {
    $state = makeAssetPipeline();
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    $entityState = PipelineEntityState::factory()->create([
        'pipeline_id' => $state->pipeline_id,
        'current_state_id' => $state->id,
        'entity_type' => Asset::class,
        'entity_id' => $asset->id,
        'branch_id' => null,
    ]);

    artisan('pipeline-states:backfill-branch')->assertSuccessful();

    expect($entityState->fresh()->branch_id)->toBe($branch->id);
});

it('does not overwrite an already-populated branch_id during backfill', function () {
    $state = makeAssetPipeline();
    $original = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => Branch::factory()->create()->id]);

    $entityState = PipelineEntityState::factory()->create([
        'pipeline_id' => $state->pipeline_id,
        'current_state_id' => $state->id,
        'entity_type' => Asset::class,
        'entity_id' => $asset->id,
        'branch_id' => $original->id,
    ]);

    artisan('pipeline-states:backfill-branch')->assertSuccessful();

    expect($entityState->fresh()->branch_id)->toBe($original->id);
});

it('writes nothing in dry-run mode', function () {
    $state = makeAssetPipeline();
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    $entityState = PipelineEntityState::factory()->create([
        'pipeline_id' => $state->pipeline_id,
        'current_state_id' => $state->id,
        'entity_type' => Asset::class,
        'entity_id' => $asset->id,
        'branch_id' => null,
    ]);

    artisan('pipeline-states:backfill-branch', ['--dry-run' => true])->assertSuccessful();

    expect($entityState->fresh()->branch_id)->toBeNull();
});
