<?php

use App\Exports\ApPaymentExport;
use App\Exports\CustomerInvoiceExport;
use App\Models\ApPayment;
use App\Models\CustomerInvoice;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class)->group('transaction-export-default-sort');

/**
 * Nilai kolom $heading untuk seluruh baris data, berurutan sesuai file ekspor.
 *
 * @return list<string>
 */
function exportColumnValues(object $export, string $heading): array
{
    $binary = Excel::raw($export, ExcelFormat::XLSX);
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $binary);

    $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
    unlink($path);

    $headings = array_shift($rows);
    $column = array_search($heading, $headings, true);

    return array_map(fn (array $row): string => (string) $row[$column], $rows);
}

beforeEach(function () {
    Setting::query()->delete();
    Setting::factory()->create(['key' => 'currency', 'value' => 'IDR', 'group' => 'regional']);
});

test('customer invoice export without a sort orders by invoice date then number', function () {
    $day = Carbon::parse('2026-03-10');

    CustomerInvoice::factory()->sent()->create([
        'invoice_number' => 'INV-B-0002',
        'invoice_date' => $day,
    ]);
    CustomerInvoice::factory()->sent()->create([
        'invoice_number' => 'INV-A-0001',
        'invoice_date' => $day->copy()->subDay(),
    ]);
    CustomerInvoice::factory()->sent()->create([
        'invoice_number' => 'INV-C-0003',
        'invoice_date' => $day,
    ]);

    $numbers = exportColumnValues(new CustomerInvoiceExport([]), 'Invoice Number');

    expect($numbers)->toBe(['INV-A-0001', 'INV-B-0002', 'INV-C-0003']);
});

test('ap payment export without a sort orders by payment date then number', function () {
    $day = Carbon::parse('2026-04-05');

    ApPayment::factory()->confirmed()->create([
        'payment_number' => 'PAY-2',
        'payment_date' => $day,
    ]);
    ApPayment::factory()->confirmed()->create([
        'payment_number' => 'PAY-1',
        'payment_date' => $day->copy()->subDay(),
    ]);

    $numbers = exportColumnValues(new ApPaymentExport([]), 'Payment Number');

    expect($numbers)->toBe(['PAY-1', 'PAY-2']);
});

test('user selected sort is kept when sort_by is valid', function () {
    CustomerInvoice::factory()->sent()->create([
        'invoice_number' => 'INV-X-0001',
        'grand_total' => 100,
    ]);
    CustomerInvoice::factory()->sent()->create([
        'invoice_number' => 'INV-X-0002',
        'grand_total' => 900,
    ]);

    $numbers = exportColumnValues(
        new CustomerInvoiceExport(['sort_by' => 'grand_total', 'sort_direction' => 'desc']),
        'Invoice Number'
    );

    expect($numbers)->toBe(['INV-X-0002', 'INV-X-0001']);
});
