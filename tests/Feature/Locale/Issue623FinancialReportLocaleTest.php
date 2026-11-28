<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function issue623LocaleJson(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

function issue623LocaleLookup(array $locale, string $dottedKey): ?string
{
    $node = $locale;

    foreach (explode('.', $dottedKey) as $segment) {
        if (! is_array($node) || ! array_key_exists($segment, $node)) {
            return null;
        }

        $node = $node[$segment];
    }

    return is_string($node) ? $node : null;
}

test('issue 623 financial report labels keep their english text and gain indonesian translations', function () {
    $en = issue623LocaleJson('en.json');
    $id = issue623LocaleJson('id.json');

    $expected = [
        'reports.financial.section.assets' => ['Assets', 'Aset'],
        'reports.financial.section.liabilities' => ['Liabilities', 'Liabilitas'],
        'reports.financial.section.equity' => ['Equity', 'Ekuitas'],
        'reports.financial.section.revenue' => ['Revenue', 'Pendapatan'],
        'reports.financial.section.expense' => ['Expense', 'Beban'],
        'reports.financial.column.account' => ['Account', 'Akun'],
        'reports.financial.column.current' => ['Current', 'Saldo Saat Ini'],
        'reports.financial.column.comparison' => ['Comparison', 'Pembanding'],
        'reports.financial.column.change' => ['Change', 'Perubahan'],
        'reports.financial.expand_all' => ['Expand all', 'Buka semua'],
        'reports.financial.collapse_all' => ['Collapse all', 'Tutup semua'],
        'reports.financial.no_accounts' => ['No accounts found', 'Tidak ada akun'],
        'reports.financial.summary' => ['Summary', 'Ringkasan'],
        'reports.financial.status.profit' => ['Profit', 'Laba'],
        'reports.financial.status.loss' => ['Loss', 'Rugi'],
        'reports.financial.balance_sheet.description' => [
            'Assets should equal liabilities plus equity.',
            'Aset harus sama dengan liabilitas ditambah ekuitas.',
        ],
        'reports.financial.balance_sheet.total_assets' => ['Total Assets', 'Total Aset'],
        'reports.financial.balance_sheet.total_liabilities_equity' => [
            'Total Liabilities & Equity',
            'Total Liabilitas & Ekuitas',
        ],
        'reports.financial.balance_sheet.position_summary' => [
            'Financial Position Summary',
            'Ringkasan Posisi Keuangan',
        ],
        'reports.financial.balance_sheet.balanced' => ['Balanced', 'Seimbang'],
        'reports.financial.balance_sheet.unbalanced' => ['Unbalanced', 'Tidak Seimbang'],
        'reports.financial.balance_sheet.difference' => ['Difference', 'Selisih'],
        'reports.financial.income_statement.description' => [
            'Net Income = Total Revenue - Total Expense.',
            'Laba Bersih = Total Pendapatan - Total Beban.',
        ],
        'reports.financial.income_statement.total_revenue' => ['Total Revenue', 'Total Pendapatan'],
        'reports.financial.income_statement.total_expense' => ['Total Expense', 'Total Beban'],
        'reports.financial.income_statement.net_income' => ['Net Income', 'Laba Bersih'],
        'reports.financial.income_statement.net_income_comparison' => [
            'Net Income (Comparison)',
            'Laba Bersih (Pembanding)',
        ],
        'reports.financial.income_statement.net_income_change' => [
            'Net Income Change',
            'Perubahan Laba Bersih',
        ],
        'reports.financial.income_statement.summary' => ['Income Summary', 'Ringkasan Laba Rugi'],
        'reports.financial.compare_none' => ['Compare: None', 'Bandingkan: Tidak Ada'],
        'reports.financial.compare_with' => ['Compare: {year}', 'Bandingkan: {year}'],
        'reports.financial.none' => ['None', 'Tidak Ada'],
        'reports.financial.loading' => ['Loading report...', 'Memuat laporan...'],
        'reports.financial.error' => ['Error loading report.', 'Gagal memuat laporan.'],
    ];

    foreach ($expected as $key => [$enText, $idText]) {
        expect(issue623LocaleLookup($en, $key))->toBe($enText, "en {$key}")
            ->and(issue623LocaleLookup($id, $key))->toBe($idText, "id {$key}");
    }
});

test('issue 623 financial report components read labels from the translation keys', function () {
    $references = [
        'resources/js/components/reports/financial/FinancialReportSection.tsx' => [
            'reports.financial.column.account',
            'reports.financial.column.current',
            'reports.financial.column.comparison',
            'reports.financial.column.change',
            'reports.financial.expand_all',
            'reports.financial.collapse_all',
            'reports.financial.no_accounts',
        ],
        'resources/js/components/reports/financial/FinancialSummaryCard.tsx' => [
            'reports.financial.summary',
        ],
        'resources/js/components/reports/financial/FinancialReportPageShell.tsx' => [
            'reports.financial.compare_none',
            'reports.financial.compare_with',
            'reports.financial.none',
            'reports.financial.loading',
            'reports.financial.error',
        ],
        'resources/js/pages/reports/balance-sheet/index.tsx' => [
            'reports.financial.balance_sheet.description',
            'reports.financial.balance_sheet.total_assets',
            'reports.financial.balance_sheet.total_liabilities_equity',
            'reports.financial.balance_sheet.position_summary',
            'reports.financial.balance_sheet.balanced',
            'reports.financial.balance_sheet.unbalanced',
            'reports.financial.balance_sheet.difference',
        ],
        'resources/js/pages/reports/income-statement/index.tsx' => [
            'reports.financial.status.profit',
            'reports.financial.status.loss',
            'reports.financial.income_statement.description',
            'reports.financial.income_statement.total_revenue',
            'reports.financial.income_statement.total_expense',
            'reports.financial.income_statement.net_income',
            'reports.financial.income_statement.net_income_comparison',
            'reports.financial.income_statement.net_income_change',
            'reports.financial.income_statement.summary',
        ],
    ];

    foreach ($references as $path => $keys) {
        $source = File::get(base_path($path));

        foreach ($keys as $key) {
            expect(str_contains($source, $key))->toBeTrue("{$path} does not reference {$key}");
        }
    }
});

test('issue 623 balance sheet and income statement no longer hardcode the translated labels', function () {
    $balanceSheet = File::get(base_path('resources/js/pages/reports/balance-sheet/index.tsx'));
    $incomeStatement = File::get(base_path('resources/js/pages/reports/income-statement/index.tsx'));

    foreach ([
        'positiveLabel="Balanced"',
        'negativeLabel={`Unbalanced',
        'title="Unbalanced"',
        '>Difference:',
    ] as $literal) {
        expect(str_contains($balanceSheet, $literal))->toBeFalse("balance sheet still hardcodes {$literal}");
    }

    foreach ([
        'positiveLabel="Profit"',
        'negativeLabel="Loss"',
        '>Total Revenue<',
        '>Net Income (Comparison)<',
    ] as $literal) {
        expect(str_contains($incomeStatement, $literal))->toBeFalse("income statement still hardcodes {$literal}");
    }
});
