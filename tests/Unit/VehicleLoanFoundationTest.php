<?php

use App\Domain\Branch\BranchResolverRegistry;
use App\Models\VehicleLoan;

uses()->group('vehicle-loans');

describe('vehicle loan foundation', function () {
    it('registers vehicle loans as a direct branch-bearing type', function () {
        $registry = app(BranchResolverRegistry::class);

        expect($registry->isRegistered(VehicleLoan::class))->toBeTrue()
            ->and($registry->branchBearingTypes())->toContain(VehicleLoan::class);
    });

    it('resolves the branch directly from the vehicle loan', function () {
        $loan = VehicleLoan::factory()->make([
            'branch_id' => 42,
            'driver_id' => null,
            'vehicle_unit_id' => null,
        ]);

        expect(app(BranchResolverRegistry::class)->resolve($loan))->toBe(42);
    });

    it('exposes the configured approval flow code and FQCN', function () {
        expect(config('vehicle_loan.approval_flow_code'))->toBeString()
            ->and(config('vehicle_loan.approvable_type'))->toBe(VehicleLoan::class)
            ->and(config('vehicle_loan.photo_disk'))->not->toBe('public');
    });

    it('does not hardcode company identity in config', function () {
        expect(config('vehicle_loan.company_name'))->toBeNull()
            ->and(config('vehicle_loan.company_logo'))->toBeNull();
    });
});
