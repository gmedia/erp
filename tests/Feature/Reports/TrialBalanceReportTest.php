<?php

namespace Tests\Feature\Reports;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
});

test('trial balance seimbang dan sesuai jurnal posted pada seed', function () {
    Sanctum::actingAs($this->user, ['*']);
    $response = $this->getJson('/api/reports/trial-balance?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertStatus(200)
        ->assertJsonPath('selectedYearId', $this->fiscalYear->id);

    $report = $response->json('report');
    $rowsByCode = collect($report)->keyBy('code');

    expect((float) $rowsByCode->get('11110')['debit'])->toBe(5000000.0);
    expect((float) $rowsByCode->get('11300')['debit'])->toBe(3000000.0);
    expect((float) $rowsByCode->get('41000')['credit'])->toBe(5000000.0);
    expect((float) $rowsByCode->get('21100')['credit'])->toBe(3000000.0);

    $totalDebit = (float) collect($report)->sum(fn (array $row) => (float) $row['debit']);
    $totalCredit = (float) collect($report)->sum(fn (array $row) => (float) $row['credit']);

    expect($totalDebit)->toBe(8000000.0);
    expect($totalCredit)->toBe(8000000.0);
});
