<?php

use App\Exports\ApPaymentExport;
use App\Exports\ArReceiptExport;
use App\Exports\CreditNoteExport;
use App\Exports\CustomerInvoiceExport;
use App\Exports\PurchaseOrderExport;
use App\Exports\PurchaseRequestExport;
use App\Exports\SupplierBillExport;
use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Setting;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

uses(RefreshDatabase::class)->group('transaction-export-number-format');

/**
 * Membaca sheet pertama dari xlsx hasil ekspor.
 */
function readTransactionExportSheet(object $export): Worksheet
{
    $binary = Excel::raw($export, ExcelFormat::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $binary);

    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);

    return $sheet;
}

/**
 * Sel data baris pertama untuk kolom bernama $heading.
 */
function transactionExportCell(Worksheet $sheet, string $heading): Cell
{
    $headings = $sheet->rangeToArray('A1:' . $sheet->getHighestDataColumn() . '1', null, true, false)[0];
    $column = array_search($heading, $headings, true);

    expect($column)->not->toBeFalse("Kolom '{$heading}' tidak ditemukan");

    return $sheet->getCell([$column + 1, 2]);
}

beforeEach(function () {
    Setting::factory()->create(['key' => 'currency', 'value' => 'IDR', 'group' => 'regional']);
});

describe('CustomerInvoiceExport number formats', function () {

    test('money columns are numeric cells with currency code format', function () {
        CustomerInvoice::factory()->create([
            'subtotal' => 1000000.00,
            'grand_total' => 1110000.50,
            'amount_due' => 500000.25,
        ]);

        $sheet = readTransactionExportSheet(new CustomerInvoiceExport([]));

        foreach (['Subtotal' => 1000000.00, 'Grand Total' => 1110000.50, 'Amount Due' => 500000.25] as $heading => $value) {
            $cell = transactionExportCell($sheet, $heading);

            expect($cell->getValue())->toBeFloat()->toEqual($value)
                ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
        }
    });

    test('export exposes currency formats for money columns only', function () {
        $formats = (new CustomerInvoiceExport([]))->columnFormats();

        expect($formats)->toHaveCount(7)
            ->and(array_unique(array_values($formats)))->toBe(['"IDR" #,##0.00']);
    });
});

describe('SupplierBillExport number formats', function () {

    test('money columns are numeric cells with currency code format', function () {
        SupplierBill::factory()->create([
            'subtotal' => 750000.00,
            'grand_total' => 825000.00,
            'amount_paid' => 0,
            'amount_due' => 825000.00,
        ]);

        $sheet = readTransactionExportSheet(new SupplierBillExport([]));

        $cell = transactionExportCell($sheet, 'Grand Total');

        expect($cell->getValue())->toBeFloat()->toEqual(825000.00)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('ApPaymentExport number formats', function () {

    test('total amount is a numeric cell with currency code format', function () {
        ApPayment::factory()->create(['total_amount' => 300000.75, 'total_allocated' => 0]);

        $sheet = readTransactionExportSheet(new ApPaymentExport([]));

        $cell = transactionExportCell($sheet, 'Total Amount');

        expect($cell->getValue())->toBeFloat()->toEqual(300000.75)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('ArReceiptExport number formats', function () {

    test('total amount is a numeric cell with currency code format', function () {
        ArReceipt::factory()->create(['total_amount' => 450000.00, 'total_allocated' => 450000.00]);

        $sheet = readTransactionExportSheet(new ArReceiptExport([]));

        $cell = transactionExportCell($sheet, 'Total Amount');

        expect($cell->getValue())->toBeFloat()->toEqual(450000.00)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('PurchaseOrderExport number formats', function () {

    test('grand total is a numeric cell with currency code format', function () {
        PurchaseOrder::factory()->create(['subtotal' => 900000.00, 'grand_total' => 999000.00]);

        $sheet = readTransactionExportSheet(new PurchaseOrderExport([]));

        $cell = transactionExportCell($sheet, 'Grand Total');

        expect($cell->getValue())->toBeFloat()->toEqual(999000.00)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('CreditNoteExport number formats', function () {

    test('grand total is a numeric cell with currency code format', function () {
        CreditNote::factory()->create(['subtotal' => 200000.00, 'grand_total' => 220000.00]);

        $sheet = readTransactionExportSheet(new CreditNoteExport([]));

        $cell = transactionExportCell($sheet, 'Grand Total');

        expect($cell->getValue())->toBeFloat()->toEqual(220000.00)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('PurchaseRequestExport number formats', function () {

    test('estimated amount is a numeric cell with currency code format', function () {
        PurchaseRequest::factory()->create(['estimated_amount' => 1250000.00]);

        $sheet = readTransactionExportSheet(new PurchaseRequestExport([]));

        $cell = transactionExportCell($sheet, 'Estimated Amount');

        expect($cell->getValue())->toBeFloat()->toEqual(1250000.00)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });
});

describe('empty currency setting', function () {

    test('money columns use plain number format without currency literal', function () {
        Setting::query()->where('key', 'currency')->update(['value' => '']);
        PurchaseOrder::factory()->create(['grand_total' => 500000.00]);

        $sheet = readTransactionExportSheet(new PurchaseOrderExport([]));

        $cell = transactionExportCell($sheet, 'Grand Total');

        expect($cell->getValue())->toBeFloat()->toEqual(500000.00)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
    });
});
