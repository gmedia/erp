<?php

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('account-mappings', 'export');

describe('Account Mapping Export API', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['account_mapping']);
        actingAs($user);

        $sourceVersion = CoaVersion::factory()->create(['status' => 'archived']);
        $targetVersion = CoaVersion::factory()->create(['status' => 'active']);

        $sourceAccount = Account::factory()->create([
            'coa_version_id' => $sourceVersion->id,
            'code' => '11100',
            'name' => 'Cash',
        ]);

        $targetAccount = Account::factory()->create([
            'coa_version_id' => $targetVersion->id,
            'code' => '11110',
            'name' => 'Cash In Bank',
        ]);

        AccountMapping::create([
            'source_account_id' => $sourceAccount->id,
            'target_account_id' => $targetAccount->id,
            'type' => 'rename',
            'notes' => 'test',
        ]);
    });

    test('export endpoint returns url and filename', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
        Excel::fake();
        Storage::fake('public');

        $response = postJson('/api/account-mappings/export', []);

        $response->assertStatus(200)
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json('filename'))->toBe('account_mappings_export_2026-01-01_10-00-00.xlsx');
        Excel::assertStored('exports/account_mappings_export_2026-01-01_10-00-00.xlsx', 'public');
        Carbon::setTestNow();
    });
});

