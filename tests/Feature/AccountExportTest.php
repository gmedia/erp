<?php

use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('accounts', 'export');

describe('Account Export API', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['account']);
        actingAs($user);
        $this->coaVersion = CoaVersion::factory()->create();
    });

    test('export endpoint returns url and filename', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
        Excel::fake();
        Storage::fake('public');

        $response = postJson('/api/accounts/export', [
            'coa_version_id' => $this->coaVersion->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json('filename'))->toBe('accounts_export_2026-01-01_10-00-00.xlsx');
        Excel::assertStored('exports/accounts_export_2026-01-01_10-00-00.xlsx', 'public');
        Carbon::setTestNow();
    });
});
