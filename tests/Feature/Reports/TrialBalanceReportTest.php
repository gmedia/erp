<?php

namespace Tests\Feature\Reports;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
});

test('trial balance seimbang dan sesuai jurnal posted pada seed', function () {
    actingAs($this->user)
        ->get(route('reports.trial-balance', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/trial-balance/index')
            ->where('selectedYearId', $this->fiscalYear->id)
            ->has('fiscalYears')
            ->where('report', function ($report) {
                if ($report instanceof \Illuminate\Support\Collection) {
                    $report = $report->all();
                }

                if (!is_array($report)) {
                    return false;
                }

                $rowsByCode = collect($report)->keyBy('code');

                $cashInBank = $rowsByCode->get('11110');
                $inventory = $rowsByCode->get('11300');
                $sales = $rowsByCode->get('41000');
                $accountsPayable = $rowsByCode->get('21100');

                if (!$cashInBank || !$inventory || !$sales || !$accountsPayable) {
                    return false;
                }

                $cashInBankDebit = (float) $cashInBank['debit'];
                $inventoryDebit = (float) $inventory['debit'];
                $salesCredit = (float) $sales['credit'];
                $apCredit = (float) $accountsPayable['credit'];

                $totalDebit = (float) collect($report)->sum(fn (array $row) => (float) $row['debit']);
                $totalCredit = (float) collect($report)->sum(fn (array $row) => (float) $row['credit']);

                return $cashInBankDebit === 5000000.0
                    && $inventoryDebit === 3000000.0
                    && $salesCredit === 5000000.0
                    && $apCredit === 3000000.0
                    && $totalDebit === 8000000.0
                    && $totalCredit === 8000000.0;
            })
        );
});
