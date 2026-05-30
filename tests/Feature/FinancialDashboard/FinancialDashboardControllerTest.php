<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\CreatesTestUserWithPermissions;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class, CreatesTestUserWithPermissions::class)->group('financial-dashboard');

describe('Financial Dashboard API', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['report']), ['*']);
    });

    test('returns correct JSON structure', function () {
        FiscalYear::factory()->create(['status' => 'open']);

        $response = getJson('/api/financial-dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'fiscal_years',
                'selected_year_id',
                'comparison_year_id',
                'kpis' => [
                    'revenue' => ['value', 'change', 'comparison_value'],
                    'expenses' => ['value', 'change', 'comparison_value'],
                    'net_income' => ['value', 'change', 'comparison_value'],
                    'total_assets' => ['value', 'change', 'comparison_value'],
                    'total_liabilities' => ['value', 'change', 'comparison_value'],
                    'equity' => ['value', 'change', 'comparison_value'],
                    'cash_balance' => ['value', 'change', 'comparison_value'],
                ],
                'cash_flow_summary' => ['inflow', 'outflow', 'net'],
                'expense_breakdown',
            ]);
    });

    test('returns empty KPIs when no fiscal year exists', function () {
        $response = getJson('/api/financial-dashboard');

        $response->assertOk()
            ->assertJsonPath('selected_year_id', null);
    });

    test('selects preferred fiscal year by default', function () {
        $oldYear = FiscalYear::factory()->create([
            'status' => 'closed',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
        ]);
        $currentYear = FiscalYear::factory()->create([
            'status' => 'open',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        $response = getJson('/api/financial-dashboard');

        $response->assertOk()
            ->assertJsonPath('selected_year_id', $currentYear->id);
    });

    test('accepts fiscal_year_id query parameter', function () {
        $year = FiscalYear::factory()->create(['status' => 'open']);

        $response = getJson("/api/financial-dashboard?fiscal_year_id={$year->id}");

        $response->assertOk()
            ->assertJsonPath('selected_year_id', $year->id);
    });

    test('calculates KPIs from posted journal entries', function () {
        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $coaVersion = CoaVersion::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'active',
        ]);

        $revenueAccount = Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'type' => 'revenue',
            'normal_balance' => 'credit',
            'level' => 1,
            'parent_id' => null,
        ]);

        $expenseAccount = Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'type' => 'expense',
            'normal_balance' => 'debit',
            'level' => 1,
            'parent_id' => null,
        ]);

        $journalEntry = JournalEntry::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'posted',
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 500000,
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $expenseAccount->id,
            'debit' => 300000,
            'credit' => 0,
        ]);

        $response = getJson("/api/financial-dashboard?fiscal_year_id={$fiscalYear->id}");

        $response->assertOk();

        $kpis = $response->json('kpis');
        expect((float) $kpis['revenue']['value'])->toBe(500000.0)
            ->and((float) $kpis['expenses']['value'])->toBe(300000.0)
            ->and((float) $kpis['net_income']['value'])->toBe(200000.0);
    });

    test('requires authentication', function () {
        // Reset auth
        app('auth')->forgetGuards();

        $response = getJson('/api/financial-dashboard');

        $response->assertUnauthorized();
    });
});
