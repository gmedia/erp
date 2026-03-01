<?php

use App\Exports\ApprovalDelegations\ApprovalDelegationExport;
use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class)->group('approval-delegations');

describe('Approval Delegation Export API Ending', function () {
    beforeEach(function () {
        // Assume user has export permission
        $user = createTestUserWithPermissions([
            'approval-delegation',
            'approval-delegation.export'
        ]);

        actingAs($user);
    });

    test('can export approval delegations to excel', function () {
        Excel::fake();
        $now = now();
        \Carbon\Carbon::setTestNow($now);

        ApprovalDelegation::factory()->count(3)->create();

        $response = get('/api/approval-delegations/export');

        $response->assertOk();
        
        Excel::assertDownloaded('approval-delegations-' . $now->format('Y-m-d-His') . '.xlsx');
    });

    test('can export with search filter', function () {
        Excel::fake();
        $now = now();
        \Carbon\Carbon::setTestNow($now);

        ApprovalDelegation::factory()->create(['reason' => 'Annual Leave']);
        ApprovalDelegation::factory()->create(['reason' => 'Business Trip']);

        $response = get('/api/approval-delegations/export?search=Annual');

        $response->assertOk();
        
        Excel::assertDownloaded('approval-delegations-' . $now->format('Y-m-d-His') . '.xlsx', function (ApprovalDelegationExport $export) {
            // ApprovalDelegationExport uses FromQuery, so it has a query() method
            return $export->query()->count() === 1;
        });
    });

    test('can export with status filter', function () {
        Excel::fake();
        $now = now();
        \Carbon\Carbon::setTestNow($now);

        ApprovalDelegation::factory()->create(['is_active' => true]);
        ApprovalDelegation::factory()->create(['is_active' => false]);

        $response = get('/api/approval-delegations/export?is_active=true');

        $response->assertOk();
        
        Excel::assertDownloaded('approval-delegations-' . $now->format('Y-m-d-His') . '.xlsx');
    });
});
