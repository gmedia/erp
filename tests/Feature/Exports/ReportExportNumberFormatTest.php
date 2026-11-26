<?php

use App\Exports\ApAgingReportExport;
use App\Exports\ArAgingReportExport;
use App\Exports\CustomerStatementReportExport;
use App\Exports\GoodsReceiptReportExport;
use App\Exports\PurchaseHistoryReportExport;
use App\Exports\PurchaseOrderStatusReportExport;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

uses(RefreshDatabase::class)->group('report-export-number-format');

/**
 * Membaca sheet pertama dari xlsx hasil ekspor laporan.
 */
function readReportExportSheet(object $export): Worksheet
{
    $binary = Excel::raw($export, ExcelFormat::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $binary);

    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);

    return $sheet;
}

/**
 * Sel untuk kolom $heading pada baris yang kolom identitasnya ($identityHeading = $identityValue).
 * Baris dicari berdasarkan identitas, bukan posisi, karena urutan ekspor tidak dijamin.
 */
function reportExportCell(
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

beforeEach(function () {
    Setting::query()->delete();
    Setting::factory()->create(['key' => 'currency', 'value' => 'IDR', 'group' => 'regional']);
});

describe('ApAgingReportExport number formats', function () {

    test('money columns are numeric cells with currency code format', function () {
        // State confirmed() menetapkan amount_paid = 0 dan amount_due = grand_total; nilai input
        // lain akan ditimpa, jadi test memakai nilai yang konsisten dengan status tersebut.
        $bill = SupplierBill::factory()->confirmed()->create([
            'bill_number' => 'BILL-EXP-0001',
            'supplier_id' => Supplier::factory()->create()->id,
            'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'grand_total' => 1000000.50,
        ]);

        $sheet = readReportExportSheet(new ApAgingReportExport([]));

        foreach (['Grand Total' => 1000000.50, 'Amount Due' => 1000000.50] as $heading => $value) {
            $cell = reportExportCell($sheet, $heading, 'Bill Number', $bill->bill_number);

            expect($cell->getValue())->toBeFloat()->toEqual($value)
                ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
        }
    });
});

describe('ArAgingReportExport number formats', function () {

    test('grand total and aging buckets are numeric cells with currency code format', function () {
        $invoice = CustomerInvoice::factory()->sent()->create([
            'invoice_number' => 'INV-EXP-0001',
            'customer_id' => Customer::factory()->create()->id,
            'fiscal_year_id' => FiscalYear::factory()->create()->id,
            'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'grand_total' => 480000.75,
            'amount_due' => 480000.75,
        ]);

        $sheet = readReportExportSheet(new ArAgingReportExport([]));

        $cell = reportExportCell($sheet, 'Grand Total', 'Invoice Number', $invoice->invoice_number);

        expect($cell->getValue())->toBeFloat()->toEqual(480000.75)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('CustomerStatementReportExport number formats', function () {

    test('export declares money columns for the currency format', function () {
        $formats = (new CustomerStatementReportExport([]))->columnFormats();

        expect(array_unique(array_values($formats)))->toBe(['"IDR" #,##0.00'])
            ->and($formats)->toHaveCount(5);
    });
});

describe('PurchaseHistoryReportExport number formats', function () {

    test('quantities use plain number format and total purchase value uses currency format', function () {
        $formats = (new PurchaseHistoryReportExport([]))->columnFormats();

        expect(array_values($formats))->toContain('#,##0.00')
            ->and(array_values($formats))->toContain('"IDR" #,##0.00')
            ->and($formats)->toHaveCount(5);
    });
});

describe('PurchaseOrderStatusReportExport number formats', function () {

    test('grand total is a currency format and quantities are plain numbers', function () {
        $formats = (new PurchaseOrderStatusReportExport([]))->columnFormats();

        expect(array_values($formats))->toContain('"IDR" #,##0.00')
            ->and(array_values($formats))->toContain('#,##0.00')
            ->and($formats)->toHaveCount(6);
    });
});

describe('GoodsReceiptReportExport number formats', function () {

    test('total receipt value is a currency format and quantities are plain numbers', function () {
        $formats = (new GoodsReceiptReportExport([]))->columnFormats();

        expect(array_values($formats))->toContain('"IDR" #,##0.00')
            ->and(array_values($formats))->toContain('#,##0.00')
            ->and($formats)->toHaveCount(5);
    });
});
