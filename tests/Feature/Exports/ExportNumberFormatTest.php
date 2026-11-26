<?php

use App\Exports\InventoryValuationReportExport;
use App\Exports\ProductExport;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

uses(RefreshDatabase::class)->group('export-number-format');

/**
 * Membaca sheet pertama dari xlsx hasil ekspor.
 */
function readExportSheet(object $export): Worksheet
{
    $binary = Excel::raw($export, ExcelFormat::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $binary);

    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);

    return $sheet;
}

describe('ProductExport number formats', function () {

    test('cost and selling price are written as numeric cells with IDR code from currency setting', function () {
        Setting::factory()->create(['key' => 'currency', 'value' => 'IDR', 'group' => 'regional']);
        Setting::factory()->create(['key' => 'number_format_decimal', 'value' => ',', 'group' => 'regional']);
        Setting::factory()->create(['key' => 'number_format_thousand', 'value' => '.', 'group' => 'regional']);

        Product::factory()->create([
            'cost' => 1500000.50,
            'selling_price' => 2250000.00,
        ]);

        $sheet = readExportSheet(new ProductExport([]));
        $headings = $sheet->rangeToArray('A1:J1', null, true, false)[0];
        $costCol = array_search('Cost', $headings, true) + 1;
        $priceCol = array_search('Selling Price', $headings, true) + 1;

        $costCell = $sheet->getCell([$costCol, 2]);
        $priceCell = $sheet->getCell([$priceCol, 2]);

        expect($costCell->getValue())->toBeFloat()->toEqual(1500000.50)
            ->and($priceCell->getValue())->toBeFloat()->toEqual(2250000.00)
            ->and($costCell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00')
            ->and($priceCell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"IDR" #,##0.00');
    });

    test('currency code in the number format follows the company currency setting', function () {
        Setting::factory()->create(['key' => 'currency', 'value' => 'USD', 'group' => 'regional']);
        Product::factory()->create(['cost' => 99.5, 'selling_price' => 120]);

        $sheet = readExportSheet(new ProductExport([]));
        $headings = $sheet->rangeToArray('A1:J1', null, true, false)[0];
        $costCol = array_search('Cost', $headings, true) + 1;

        $cell = $sheet->getCell([$costCol, 2]);

        expect($cell->getValue())->toBeFloat()->toEqual(99.5)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('"USD" #,##0.00');
    });
});

describe('empty currency setting', function () {

    test('currency column uses plain number format without symbol when currency setting is empty', function () {
        Setting::factory()->create(['key' => 'currency', 'value' => '', 'group' => 'regional']);

        $export = new InventoryValuationReportExport([]);
        $formats = $export->columnFormats();

        expect($formats)->toHaveKey('I', '#,##0.00')
            ->and($formats)->toHaveKey('J', '#,##0.00');
    });

    test('currency column uses plain number format when currency setting is missing', function () {
        $export = new InventoryValuationReportExport([]);
        $formats = $export->columnFormats();

        expect($formats)->toHaveKey('I', '#,##0.00')
            ->and($formats)->toHaveKey('J', '#,##0.00');
    });

    test('product export cost cell has no currency literal when currency setting is empty', function () {
        Setting::factory()->create(['key' => 'currency', 'value' => '', 'group' => 'regional']);
        Product::factory()->create(['cost' => 500, 'selling_price' => 700]);

        $sheet = readExportSheet(new ProductExport([]));
        $headings = $sheet->rangeToArray('A1:J1', null, true, false)[0];
        $costCol = array_search('Cost', $headings, true) + 1;

        $cell = $sheet->getCell([$costCol, 2]);

        expect($cell->getValue())->toBeFloat()->toEqual(500.0)
            ->and($cell->getStyle()->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
    });
});

describe('InventoryValuationReportExport number formats', function () {

    test('exposes numeric and currency column formats for the value columns', function () {
        Setting::factory()->create(['key' => 'currency', 'value' => 'IDR', 'group' => 'regional']);

        $export = new InventoryValuationReportExport([]);
        $formats = $export->columnFormats();

        // Urutan heading: A Product Code ... H Quantity On Hand, I Average Cost, J Stock Value
        expect($formats)->toHaveKey('H', '#,##0.00')
            ->and($formats)->toHaveKey('I', '"IDR" #,##0.00')
            ->and($formats)->toHaveKey('J', '"IDR" #,##0.00')
            ->and($formats)->not->toHaveKey('A')
            ->and($formats)->not->toHaveKey('K');
    });
});
