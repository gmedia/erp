<?php

use App\Actions\Approvals\TriggerApprovalAction;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('approval-monitoring');

function makeApprovalFlowFor(string $approvableType): ApprovalFlow
{
    $flow = ApprovalFlow::create([
        'name' => 'Flow',
        'code' => 'flow_' . strtolower(class_basename($approvableType)),
        'approvable_type' => $approvableType,
        'is_active' => true,
    ]);

    ApprovalFlowStep::create([
        'approval_flow_id' => $flow->id,
        'step_order' => 1,
        'name' => 'Step 1',
        'approver_type' => 'user',
        'required_action' => 'approve',
    ]);

    return $flow;
}

it('keeps branch_id in the ApprovalRequest fillable contract', function () {
    expect((new ApprovalRequest)->getFillable())->toContain('branch_id');
});

it('populates branch_id from a direct-branch approvable when triggering approval', function () {
    makeApprovalFlowFor(Asset::class);
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);
    Sanctum::actingAs(User::factory()->create());

    $request = app(TriggerApprovalAction::class)->execute($asset, []);

    expect($request)->not->toBeNull()
        ->and($request->branch_id)->toBe($branch->id);
});

it('populates branch_id from a warehouse-based approvable when triggering approval', function () {
    makeApprovalFlowFor(PurchaseOrder::class);
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $po = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
    Sanctum::actingAs(User::factory()->create());

    $request = app(TriggerApprovalAction::class)->execute($po->fresh(), []);

    expect($request)->not->toBeNull()
        ->and($request->branch_id)->toBe($branch->id);
});

it('backfills branch_id from a direct-branch approvable', function () {
    $flow = makeApprovalFlowFor(Asset::class);
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    $request = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => Asset::class,
        'approvable_id' => $asset->id,
        'branch_id' => null,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => User::factory()->create()->id,
        'submitted_at' => now(),
    ]);

    artisan('approval-requests:backfill-branch')->assertSuccessful();

    expect($request->fresh()->branch_id)->toBe($branch->id);
});

it('does not overwrite an already-populated branch_id during backfill', function () {
    $flow = makeApprovalFlowFor(Asset::class);
    $original = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => Branch::factory()->create()->id]);

    $request = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => Asset::class,
        'approvable_id' => $asset->id,
        'branch_id' => $original->id,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => User::factory()->create()->id,
        'submitted_at' => now(),
    ]);

    artisan('approval-requests:backfill-branch')->assertSuccessful();

    expect($request->fresh()->branch_id)->toBe($original->id);
});

it('writes nothing in dry-run mode', function () {
    $flow = makeApprovalFlowFor(Asset::class);
    $branch = Branch::factory()->create();
    $asset = Asset::factory()->create(['branch_id' => $branch->id]);

    $request = ApprovalRequest::create([
        'approval_flow_id' => $flow->id,
        'approvable_type' => Asset::class,
        'approvable_id' => $asset->id,
        'branch_id' => null,
        'current_step_order' => 1,
        'status' => 'pending',
        'submitted_by' => User::factory()->create()->id,
        'submitted_at' => now(),
    ]);

    artisan('approval-requests:backfill-branch', ['--dry-run' => true])->assertSuccessful();

    expect($request->fresh()->branch_id)->toBeNull();
});
