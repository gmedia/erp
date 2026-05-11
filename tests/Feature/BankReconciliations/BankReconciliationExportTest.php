<?php

use App\Models\BankReconciliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('bank-reconciliations');

test('it exports bank reconciliations to xlsx', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['bank_reconciliation']), ['*']);
    BankReconciliation::factory()->count(2)->create();

    $response = postJson('/api/bank-reconciliations/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
});
