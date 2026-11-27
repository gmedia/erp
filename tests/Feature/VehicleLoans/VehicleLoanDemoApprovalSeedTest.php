<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStep;
use App\Models\User;
use App\Models\VehicleLoan;
use Database\Seeders\AssetCategorySeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\CoaSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\VehicleLoanApprovalFlowSeeder;
use Database\Seeders\VehicleLoanSampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('vehicle-loans');

function seedVehicleLoanDemoFixtures(): void
{
    User::factory()->create(['email' => config('app.admin')]);

    foreach ([
        BranchSeeder::class,
        DepartmentSeeder::class,
        PositionSeeder::class,
        AssetCategorySeeder::class,
        CoaSeeder::class,
        VehicleLoanApprovalFlowSeeder::class,
        VehicleLoanSampleDataSeeder::class,
    ] as $seederClass) {
        Artisan::call('db:seed', ['--class' => $seederClass, '--force' => true]);
    }
}

it('seeds a two-step demo flow where in_progress means step 1 approved and step 2 pending', function () {
    seedVehicleLoanDemoFixtures();

    $flow = ApprovalFlow::where('code', config('vehicle_loan.approval_flow_code'))->firstOrFail();
    expect(ApprovalFlowStep::where('approval_flow_id', $flow->id)->count())->toBe(2);

    $loan = VehicleLoan::where('code', 'VL-2026-0003')->firstOrFail();
    expect($loan->status)->toBe('in_progress');

    $approval = ApprovalRequest::where('approvable_type', VehicleLoan::class)
        ->where('approvable_id', $loan->id)
        ->firstOrFail();

    expect($approval->status)->toBe('pending')
        ->and($approval->current_step_order)->toBe(2);

    $steps = ApprovalRequestStep::where('approval_request_id', $approval->id)
        ->orderBy('step_order')
        ->get();

    expect($steps)->toHaveCount(2)
        ->and($steps[0]->status)->toBe('approved')
        ->and($steps[0]->acted_at)->not->toBeNull()
        ->and($steps[1]->status)->toBe('pending')
        ->and($steps[1]->acted_at)->toBeNull();
});

it('keeps demo approval and loan counts identical when seeded again', function () {
    seedVehicleLoanDemoFixtures();

    $counts = fn (): array => [
        'loans' => VehicleLoan::count(),
        'flow_steps' => ApprovalFlowStep::count(),
        'approvals' => ApprovalRequest::where('approvable_type', VehicleLoan::class)->count(),
        'approval_steps' => ApprovalRequestStep::count(),
    ];

    $first = $counts();

    Artisan::call('db:seed', ['--class' => VehicleLoanApprovalFlowSeeder::class, '--force' => true]);
    Artisan::call('db:seed', ['--class' => VehicleLoanSampleDataSeeder::class, '--force' => true]);

    expect($counts())->toBe($first);
});
