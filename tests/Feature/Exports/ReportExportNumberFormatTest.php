<?php

use App\Exports\ApAgingReportExport;
use App\Exports\ArAgingReportExport;
use App\Exports\AssetExport;
use App\Exports\BookValueDepreciationExport;
use App\Exports\CustomerStatementReportExport;
use App\Exports\GoodsReceiptReportExport;
use App\Exports\InventoryStocktakeVarianceExport;
use App\Exports\MaintenanceCostExport;
use App\Exports\PurchaseHistoryReportExport;
use App\Exports\PurchaseOrderStatusReportExport;
use App\Exports\StockAdjustmentReportExport;
use App\Exports\StockMonitorExport;
use App\Exports\StockMovementReportExport;
use App\Exports\StockMovementsExport;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\Warehouse;
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
        SupplierBill::factory()->confirmed()->create([
            'bill_number' => 'BILL-EXP-0001',
            'supplier_id' => Supplier::factory()->create()->id,
            'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'grand_total' => 1000000.50,
        ]);

        $sheet = readReportExportSheet(new ApAgingReportExport([]));

        foreach (['Grand Total' => 1000000.50, 'Amount Due' => 1000000.50] as $heading => $value) {
            $cell = reportExportCell($sheet, $heading, 'Bill Number', 'BILL-EXP-0001');

            expect($cell->getValue())->toBeFloat()->toEqual($value)
                ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
        }
    });
});

describe('ArAgingReportExport number formats', function () {

    test('grand total and aging buckets are numeric cells with currency code format', function () {
        CustomerInvoice::factory()->sent()->create([
            'invoice_number' => 'INV-EXP-0001',
            'customer_id' => Customer::factory()->create()->id,
            'fiscal_year_id' => FiscalYear::factory()->create()->id,
            'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'grand_total' => 480000.75,
            'amount_due' => 480000.75,
        ]);

        $sheet = readReportExportSheet(new ArAgingReportExport([]));

        $cell = reportExportCell($sheet, 'Grand Total', 'Invoice Number', 'INV-EXP-0001');

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

describe('StockMovementReportExport number formats', function () {

    test('ending balance is a plain number and the row is found by product code', function () {
        $product = Product::factory()->create(['code' => 'SM-RPT-0001']);
        $warehouse = Warehouse::factory()->create(['code' => 'WH-SMR-01']);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity_in' => 15,
            'quantity_out' => 0,
            'balance_after' => 15,
            'moved_at' => now(),
        ]);

        $sheet = readReportExportSheet(new StockMovementReportExport([]));

        $cell = reportExportCell($sheet, 'Ending Balance', 'Product Code', 'SM-RPT-0001');

        expect($cell->getValue())->toBeFloat()->toEqual(15.0)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
    });

    test('quantity columns are plain number formats with no currency symbol', function () {
        $formats = (new StockMovementReportExport([]))->columnFormats();

        expect(array_values($formats))->each->toBe('#,##0.00')
            ->and($formats)->toHaveCount(3);
    });
});

describe('StockMovementsExport number formats', function () {

    test('quantities are plain numbers and unit cost uses the currency format', function () {
        $formats = (new StockMovementsExport([]))->columnFormats();

        expect(array_values($formats))->toContain('#,##0.00')
            ->and(array_values($formats))->toContain('"IDR" #,##0.00')
            ->and($formats)->toHaveCount(5);
    });
});

describe('StockMonitorExport number formats', function () {

    test('stock value is a currency cell and quantity on hand is a plain number', function () {
        $product = Product::factory()->create(['code' => 'SM-MON-0001']);
        $warehouse = Warehouse::factory()->create(['code' => 'WH-SMM-01']);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity_in' => 8,
            'quantity_out' => 0,
            'balance_after' => 8,
            'average_cost_after' => 2500,
            'moved_at' => now(),
        ]);

        $sheet = readReportExportSheet(new StockMonitorExport([]));

        // Format dibaca tepat setelah sel diambil: getStyle() pada sel lain di sheet yang sama
        // dapat mengubah format sel yang sudah diambil sebelumnya.
        $qty = reportExportCell($sheet, 'Quantity On Hand', 'Product Code', 'SM-MON-0001');
        $qtyValue = $qty->getValue();
        $qtyFormat = $qty->getStyle()->getNumberFormat()->getFormatCode();

        $value = reportExportCell($sheet, 'Stock Value', 'Product Code', 'SM-MON-0001');
        $valueValue = $value->getValue();
        $valueFormat = $value->getStyle()->getNumberFormat()->getFormatCode();

        expect($qtyValue)->toBeFloat()->toEqual(8.0)
            ->and($qtyFormat)->toBe('#,##0.00')
            ->and($valueValue)->toBeFloat()->toEqual(20000.0)
            ->and($valueFormat)->toBe('"IDR" #,##0.00');
    });
});

describe('InventoryStocktakeVarianceExport number formats', function () {

    test('system, counted and variance quantities are plain numbers', function () {
        $formats = (new InventoryStocktakeVarianceExport([]))->columnFormats();

        expect(array_values($formats))->each->toBe('#,##0.00')
            ->and($formats)->toHaveCount(3);
    });
});

describe('StockAdjustmentReportExport number formats', function () {

    test('total adjustment value is a currency format and adjusted quantity is plain', function () {
        $formats = (new StockAdjustmentReportExport([]))->columnFormats();

        expect(array_values($formats))->toContain('"IDR" #,##0.00')
            ->and(array_values($formats))->toContain('#,##0.00')
            ->and($formats)->toHaveCount(2);
    });
});

describe('BookValueDepreciationExport number formats', function () {

    test('money columns use the currency format and useful life stays a plain integer', function () {
        $formats = (new BookValueDepreciationExport([]))->columnFormats();

        expect(array_values($formats))->each->toBe('"IDR" #,##0.00')
            ->and($formats)->toHaveCount(4);
    });
});

describe('MaintenanceCostExport number formats', function () {

    test('cost is a currency format', function () {
        $formats = (new MaintenanceCostExport([]))->columnFormats();

        expect(array_values($formats))->toBe(['"IDR" #,##0.00'])
            ->and($formats)->toHaveCount(1);
    });
});

describe('AssetExport number formats', function () {

    test('purchase cost is a currency format', function () {
        $formats = (new AssetExport([]))->columnFormats();

        expect(array_values($formats))->toBe(['"IDR" #,##0.00'])
            ->and($formats)->toHaveCount(1);
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
