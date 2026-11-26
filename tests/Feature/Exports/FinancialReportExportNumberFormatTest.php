<?php

use App\Exports\BalanceSheetReportExport;
use App\Exports\BudgetExport;
use App\Exports\BudgetVarianceExport;
use App\Exports\CashFlowReportExport;
use App\Exports\ComparativeReportExport;
use App\Exports\GeneralLedgerReportExport;
use App\Exports\IncomeStatementReportExport;
use App\Exports\TrialBalanceFinancialReportExport;
use App\Exports\TrialBalanceReportExport;
use App\Models\Setting;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

uses(RefreshDatabase::class)->group('financial-report-export-number-format');

/**
 * Membaca sheet pertama dari xlsx hasil ekspor.
 */
function readFinancialExportSheet(object $export): Worksheet
{
    $binary = Excel::raw($export, ExcelFormat::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $binary);

    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);

    return $sheet;
}

/**
 * Sel pada kolom $heading di baris yang kolom $identityHeading-nya bernilai $identityValue.
 * Baris dicari berdasarkan identitas (kode/nomor/label), bukan posisi.
 */
function financialExportCell(
    Worksheet $sheet,
    string $heading,
    string $identityHeading,
    string $identityValue
): Cell {
    $rows = $sheet->rangeToArray(
        'A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow(),
        null,
        true,
        false
    );
    $headings = array_shift($rows);

    $column = array_search($heading, $headings, true);
    $identityColumn = array_search($identityHeading, $headings, true);

    expect($column)->not->toBeFalse("Kolom '{$heading}' tidak ditemukan")
        ->and($identityColumn)->not->toBeFalse("Kolom '{$identityHeading}' tidak ditemukan");

    foreach ($rows as $index => $row) {
        if ((string) $row[$identityColumn] === $identityValue) {
            return $sheet->getCell([$column + 1, $index + 2]);
        }
    }

    throw new RuntimeException("Baris dengan {$identityHeading} '{$identityValue}' tidak ditemukan");
}

/**
 * Baris laporan keuangan berpola (section, code, name, level, balance, comparison, change, change%).
 */
function financialReportRow(string $section, string $code, string $name, float $balance): array
{
    return [$section, $code, $name, 0, $balance, 0.0, $balance, 0.0];
}

beforeEach(function () {
    Setting::query()->delete();
    Setting::factory()->create(['key' => 'currency', 'value' => 'IDR', 'group' => 'regional']);
});

describe('laporan posisi keuangan dan laba rugi', function () {

    test('kolom saldo dan selisih memakai format mata uang, persen memakai angka biasa', function () {
        $formats = (new BalanceSheetReportExport(['fiscal_year_id' => 1]))->columnFormats();

        expect($formats)->toBe([
            'E' => '"IDR" #,##0.00',
            'F' => '"IDR" #,##0.00',
            'G' => '"IDR" #,##0.00',
            'H' => '#,##0.00',
        ]);
    });

    test('saldo akun ditulis sebagai angka mentah dan judul seksi tetap teks', function () {
        $service = $this->mock(FinancialReportService::class);
        $service->shouldReceive('getBalanceSheet')->once()->andReturn([
            'assets' => [[
                'code' => '1101',
                'name' => 'Kas',
                'level' => 0,
                'balance' => '1500000.50',
                'comparison_balance' => 0,
                'change' => '1500000.50',
                'change_percentage' => 0,
                'children' => [],
            ]],
            'liabilities' => [],
            'equity' => [],
            'totals' => [
                'assets' => '1500000.50',
                'comparison_assets' => 0,
                'change_assets' => '1500000.50',
                'change_percentage_assets' => 0,
                'liabilities' => 0,
                'comparison_liabilities' => 0,
                'change_liabilities' => 0,
                'equity' => 0,
                'comparison_equity' => 0,
                'change_equity' => 0,
            ],
        ]);

        $sheet = readFinancialExportSheet(new BalanceSheetReportExport(['fiscal_year_id' => 1]));

        $cell = financialExportCell($sheet, 'Balance', 'Code', '1101');
        expect($cell->getValue())->toBeFloat()->toEqual(1500000.50)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');

        // Baris judul/ringkasan "Total Assets" berisi teks di kolom label; nilainya tetap angka.
        $label = financialExportCell($sheet, 'Name', 'Name', 'Total Assets');
        expect($label->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($label->getValue())->toBe('Total Assets');
    });

    test('laba rugi memakai format mata uang untuk saldo dan persen tetap angka biasa', function () {
        $formats = (new IncomeStatementReportExport(['fiscal_year_id' => 1]))->columnFormats();

        expect(array_values($formats))->toBe([
            '"IDR" #,##0.00',
            '"IDR" #,##0.00',
            '"IDR" #,##0.00',
            '#,##0.00',
        ]);
    });

    test('komparatif memakai format dan total seksi tetap teks di kolom nama', function () {
        $formats = (new ComparativeReportExport(['fiscal_year_id' => 1]))->columnFormats();

        expect($formats)->toHaveCount(4)
            ->and($formats['E'])->toBe('"IDR" #,##0.00')
            ->and($formats['H'])->toBe('#,##0.00');
    });
});

describe('laporan arus kas, neraca saldo, dan buku besar', function () {

    test('arus kas: kolom inflow, outflow, net berformat mata uang dan label ringkasan tetap teks', function () {
        $service = $this->mock(FinancialReportService::class);
        $service->shouldReceive('getCashFlow')->once()->andReturn([[
            'code' => '1101',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'level' => 0,
            'inflow' => '250000.00',
            'outflow' => 100000,
        ]]);
        $service->shouldReceive('getOpeningCashBalance')->once()->andReturn(0.0);
        $service->shouldReceive('getCashFlowTotals')->once()->andReturn([
            'operating_cash_flow' => 150000.0,
            'investing_cash_flow' => 0.0,
            'financing_cash_flow' => 0.0,
            'net_change' => 150000.0,
        ]);

        $export = new CashFlowReportExport(['fiscal_year_id' => 1]);
        $formats = $export->columnFormats();
        $sheet = readFinancialExportSheet($export);

        expect(array_values($formats))->toContain('"IDR" #,##0.00');

        $inflow = financialExportCell($sheet, 'Inflow', 'Code', '1101');
        expect($inflow->getValue())->toBeFloat()->toEqual(250000.0)
            ->and($inflow->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');

        // Baris ringkasan "Operating activities": label di kolom pertama tetap teks, bukan angka.
        $summary = financialExportCell($sheet, 'Code', 'Code', 'Operating activities');
        expect($summary->getDataType())->toBe(DataType::TYPE_STRING);
    });

    test('neraca saldo keuangan: debit dan kredit mentah berformat mata uang', function () {
        $service = $this->mock(FinancialReportService::class);
        $service->shouldReceive('getTrialBalance')->once()->andReturn([[
            'code' => '1101',
            'name' => 'Kas',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'level' => 0,
            'debit' => '1200000.00',
            'credit' => 0,
            'raw_debit' => '1200000.00',
            'raw_credit' => 0,
        ]]);

        $sheet = readFinancialExportSheet(new TrialBalanceFinancialReportExport(['fiscal_year_id' => 1]));

        $debit = financialExportCell($sheet, 'Debit', 'Code', '1101');
        expect($debit->getValue())->toBeFloat()->toEqual(1200000.0)
            ->and($debit->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });

    test('neraca saldo ringkas: saldo awal dan akhir berformat mata uang', function () {
        $formats = (new TrialBalanceReportExport(['fiscal_year_id' => 1]))->columnFormats();

        expect($formats)->toHaveCount(6)
            ->and(array_unique(array_values($formats)))->toBe(['"IDR" #,##0.00']);
    });

    test('buku besar: debit, kredit, saldo berjalan berformat mata uang dan tanggal tetap teks', function () {
        $formats = (new GeneralLedgerReportExport(['fiscal_year_id' => 1]))->columnFormats();

        expect($formats)->toHaveCount(3)
            ->and(array_unique(array_values($formats)))->toBe(['"IDR" #,##0.00'])
            ->and($formats)->not->toHaveKey('A');
    });
});

describe('ekspor anggaran', function () {

    test('total anggaran berformat mata uang', function () {
        $formats = (new BudgetExport([]))->columnFormats();

        expect(array_values($formats))->toBe(['"IDR" #,##0.00']);
    });

    test('kolom alokasi dan realisasi berformat mata uang, variance persen tetap angka biasa', function () {
        $formats = (new BudgetVarianceExport(collect()))->columnFormats();

        expect($formats)->toBe([
            'F' => '"IDR" #,##0.00',
            'G' => '"IDR" #,##0.00',
            'H' => '"IDR" #,##0.00',
            'I' => '#,##0.00',
        ]);
    });

    test('variance persen yang null tetap teks N/A, bukan angka nol palsu', function () {
        $row = [
            'account_code' => '5101',
            'account_name' => 'Beban Gaji',
            'account_type' => 'expense',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'allocated' => '1000000.00',
            'actual' => '0.00',
            'available' => '1000000.00',
            'variance_percent' => null,
            'status' => 'within_budget',
        ];

        $sheet = readFinancialExportSheet(new BudgetVarianceExport(collect([$row])));

        $variance = financialExportCell($sheet, 'Variance %', 'Account Code', '5101');
        expect($variance->getValue())->toBe('N/A')
            ->and($variance->getDataType())->toBe(DataType::TYPE_STRING);
    });

    test('variance persen yang terisi ditulis sebagai angka mentah', function () {
        $row = [
            'account_code' => '5101',
            'account_name' => 'Beban Gaji',
            'account_type' => 'expense',
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'allocated' => 1000000,
            'actual' => 1250000,
            'available' => -250000,
            'variance_percent' => 25.0,
            'status' => 'over_budget',
        ];

        $sheet = readFinancialExportSheet(new BudgetVarianceExport(collect([$row])));

        $variance = financialExportCell($sheet, 'Variance %', 'Account Code', '5101');
        expect($variance->getValue())->toBeFloat()->toEqual(25.0)
            ->and($variance->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');

        $actual = financialExportCell($sheet, 'Actual', 'Account Code', '5101');
        expect($actual->getValue())->toBeFloat()->toEqual(1250000.0);
    });
});
