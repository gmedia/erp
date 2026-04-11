<?php

use App\Models\ApprovalDelegation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('approval-delegations');

describe('Approval Delegation Export API Ending', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'approval_delegation',
            'approval_delegation.export',
        ]);

        Sanctum::actingAs($user);
        Excel::fake();
        Storage::fake('public');
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    test('can export approval delegations to excel', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));

        ApprovalDelegation::factory()->count(3)->create();

        $response = postJson('/api/approval-delegations/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString()
            ->and($data['filename'])->toBe('approval_delegations_export_2026-01-01_10-00-00.xlsx');

        Excel::assertStored('exports/approval_delegations_export_2026-01-01_10-00-00.xlsx', 'public');
    });

    test('can export with search filter', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));

        ApprovalDelegation::factory()->create(['reason' => 'Annual Leave']);
        ApprovalDelegation::factory()->create(['reason' => 'Business Trip']);

        $response = postJson('/api/approval-delegations/export', ['search' => 'Annual']);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString();
    });

    test('can export with status filter', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));

        ApprovalDelegation::factory()->create(['is_active' => true]);
        ApprovalDelegation::factory()->create(['is_active' => false]);

        $response = postJson('/api/approval-delegations/export', ['is_active' => 'true']);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toBeString();
    });
});
