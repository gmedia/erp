<?php

use App\Models\ApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('approval-flows');

describe('Approval Flow Export API', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['approval_flow']);
        actingAs($user);
    });

    test('export returns download url and filename', function () {
        ApprovalFlow::factory()->count(5)->create();

        $response = postJson('/api/approval-flows/export');

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
        
        $filename = $response->json('filename');
        expect($filename)->toContain('approval_flows_export_');
    });

    test('export respects search filter', function () {
        ApprovalFlow::factory()->create(['name' => 'Target Flow']);
        ApprovalFlow::factory()->count(4)->create(['name' => 'Other Flow']);

        $response = postJson('/api/approval-flows/export', ['search' => 'Target']);

        $response->assertOk();
    });
});
