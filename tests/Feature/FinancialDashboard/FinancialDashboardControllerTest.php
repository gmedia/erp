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
        Sanctum::actingAs($this->createTestUserWithPermissions(['financial_dashboard']), ['*']);
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
                'monthly_trends',
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

    test('returns monthly trends bucketed by entry_date across multiple months without MariaDB MONTH()', function () {
        $fiscalYear = FiscalYear::factory()->create([
            'status' => 'open',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);
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

        $entries = [
            ['date' => '2024-01-15', 'revenue' => 100000.0, 'expense' => 30000.0],
            ['date' => '2024-03-10', 'revenue' => 250000.0, 'expense' => 75000.0],
            ['date' => '2024-03-25', 'revenue' => 50000.0, 'expense' => 25000.0],
            ['date' => '2024-07-01', 'revenue' => 0.0, 'expense' => 200000.0],
        ];

        foreach ($entries as $entry) {
            $journal = JournalEntry::factory()->create([
                'fiscal_year_id' => $fiscalYear->id,
                'status' => 'posted',
                'entry_date' => $entry['date'],
            ]);

            JournalEntryLine::factory()->create([
                'journal_entry_id' => $journal->id,
                'account_id' => $revenueAccount->id,
                'debit' => 0,
                'credit' => $entry['revenue'],
            ]);

            JournalEntryLine::factory()->create([
                'journal_entry_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'debit' => $entry['expense'],
                'credit' => 0,
            ]);
        }

        $response = getJson("/api/financial-dashboard?fiscal_year_id={$fiscalYear->id}");
        $response->assertOk();

        $trends = $response->json('monthly_trends');
        $byMonth = collect($trends)->keyBy('month');

        expect($trends)->toHaveCount(12);
        expect((float) $byMonth[1]['revenue'])->toBe(100000.0);
        expect((float) $byMonth[1]['expenses'])->toBe(30000.0);
        expect((float) $byMonth[3]['revenue'])->toBe(300000.0);
        expect((float) $byMonth[3]['expenses'])->toBe(100000.0);
        expect((float) $byMonth[7]['revenue'])->toBe(0.0);
        expect((float) $byMonth[7]['expenses'])->toBe(200000.0);
        expect((float) $byMonth[2]['revenue'])->toBe(0.0);
        expect((float) $byMonth[2]['expenses'])->toBe(0.0);
        expect($byMonth[3]['label'])->toBe('Mar');
    });

    test('requires authentication', function () {
        // Reset auth
        app('auth')->forgetGuards();

        $response = getJson('/api/financial-dashboard');

        $response->assertUnauthorized();
    });

    test('requires financial_dashboard permission', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions([]), ['*']);

        $response = getJson('/api/financial-dashboard');

        $response->assertForbidden();
    });

    test('returns monthly trends with correct structure', function () {
        $fiscalYear = FiscalYear::factory()->create([
            'status' => 'open',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);
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

        $journalEntry = JournalEntry::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'posted',
            'entry_date' => '2024-03-15',
        ]);

        JournalEntryLine::factory()->create([
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 100000,
        ]);

        $response = getJson("/api/financial-dashboard?fiscal_year_id={$fiscalYear->id}");

        $response->assertOk();
        $trends = $response->json('monthly_trends');

        expect($trends)->toHaveCount(12)
            ->and($trends[2]['month'])->toBe(3)
            ->and($trends[2]['label'])->toBe('Mar')
            ->and((float) $trends[2]['revenue'])->toBe(100000.0)
            ->and((float) $trends[2]['expenses'])->toBe(0.0)
            ->and((float) $trends[2]['net_income'])->toBe(100000.0);
    });
});
