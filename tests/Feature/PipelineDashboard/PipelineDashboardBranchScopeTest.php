<?php

use App\Models\Asset;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Permission;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('pipeline-dashboard');

/**
 * @param  array<string>  $permissions
 */
function makePipelineUser(?int $branchId, array $permissions): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
    ]);
    Employment::factory()->create([
        'employee_id' => $employee->id,
        'branch_id' => $branchId,
        'department_id' => null,
        'position_id' => null,
    ]);

    $ids = [];
    foreach ($permissions as $name) {
        $ids[] = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))],
        )->id;
    }
    $employee->permissions()->sync($ids);

    return $user;
}

beforeEach(function () {
    $this->branchA = Branch::factory()->create();
    $this->branchB = Branch::factory()->create();

    $this->pipeline = Pipeline::factory()->create([
        'entity_type' => Asset::class,
        'is_active' => true,
    ]);

    $this->stateReview = PipelineState::factory()->create([
        'pipeline_id' => $this->pipeline->id,
        'code' => 'review',
        'name' => 'Review',
        'type' => 'intermediate',
        'sort_order' => 1,
    ]);
});

function seedPipelineState(int $pipelineId, int $stateId, int $entityId, ?int $branchId, ?Carbon $transitionedAt = null): PipelineEntityState
{
    return PipelineEntityState::factory()->create([
        'pipeline_id' => $pipelineId,
        'current_state_id' => $stateId,
        'entity_type' => Asset::class,
        'entity_id' => $entityId,
        'branch_id' => $branchId,
        'last_transitioned_at' => $transitionedAt ?? Carbon::now()->subDays(10),
    ]);
}

it('scopes summary counts to the selected branch for a view_all_branches user', function () {
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 1, $this->branchA->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 2, $this->branchA->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 3, $this->branchB->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 4, null);

    $admin = makePipelineUser(null, ['pipeline_dashboard', 'view_all_branches']);
    Sanctum::actingAs($admin, ['*']);

    $response = getJson("/api/pipeline-dashboard/data?branch_id={$this->branchA->id}");

    $response->assertOk();
    $total = collect($response->json('summary'))->sum('count');
    expect($total)->toBe(2);
});

it('excludes null-branch rows when a branch is selected', function () {
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 1, $this->branchA->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 2, null);

    $admin = makePipelineUser(null, ['pipeline_dashboard', 'view_all_branches']);
    Sanctum::actingAs($admin, ['*']);

    $response = getJson("/api/pipeline-dashboard/data?branch_id={$this->branchA->id}");

    $total = collect($response->json('summary'))->sum('count');
    expect($total)->toBe(1);
});

it('shows all rows when no branch is selected', function () {
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 1, $this->branchA->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 2, $this->branchB->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 3, null);

    $admin = makePipelineUser(null, ['pipeline_dashboard', 'view_all_branches']);
    Sanctum::actingAs($admin, ['*']);

    $response = getJson('/api/pipeline-dashboard/data');

    $total = collect($response->json('summary'))->sum('count');
    expect($total)->toBe(3);
});

it('forces a branch-scoped employee to their own branch regardless of requested branch_id', function () {
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 1, $this->branchA->id);
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 2, $this->branchB->id);

    $employee = makePipelineUser($this->branchA->id, ['pipeline_dashboard']);
    Sanctum::actingAs($employee, ['*']);

    $response = getJson("/api/pipeline-dashboard/data?branch_id={$this->branchB->id}");

    $total = collect($response->json('summary'))->sum('count');
    expect($total)->toBe(1);
});

it('scopes stale entities to the selected branch', function () {
    $staleA = seedPipelineState($this->pipeline->id, $this->stateReview->id, 1, $this->branchA->id, Carbon::now()->subDays(10));
    seedPipelineState($this->pipeline->id, $this->stateReview->id, 2, $this->branchB->id, Carbon::now()->subDays(10));

    $admin = makePipelineUser(null, ['pipeline_dashboard', 'view_all_branches']);
    Sanctum::actingAs($admin, ['*']);

    $response = getJson("/api/pipeline-dashboard/data?branch_id={$this->branchA->id}");

    $stale = $response->json('stale_entities');
    expect($stale)->toHaveCount(1)
        ->and($stale[0]['id'])->toBe($staleA->id);
});
