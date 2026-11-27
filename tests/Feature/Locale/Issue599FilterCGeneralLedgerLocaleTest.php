<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

test('issue 599 sub-langkah C general ledger filter keys exist in both locales with english fallback text', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    $expected = [
        'filter.gl_account_required' => ['Account (Required)', 'Akun (Wajib)'],
        'filter.gl_select_account' => ['Select Account', 'Pilih Akun'],
        'filter.gl_fiscal_year' => ['Fiscal Year', 'Tahun Fiskal'],
        'filter.gl_select_fiscal_year' => ['Select Fiscal Year', 'Pilih Tahun Fiskal'],
        'filter.gl_journal_type' => ['Journal Type', 'Jenis Jurnal'],
        'filter.gl_select_journal_type' => ['Select Type', 'Pilih Jenis'],
        'filter.gl_journal_general' => ['General', 'Umum'],
        'filter.gl_journal_sales' => ['Sales', 'Penjualan'],
        'filter.gl_journal_purchase' => ['Purchase', 'Pembelian'],
        'filter.gl_journal_cash_receipt' => ['Cash Receipt', 'Penerimaan Kas'],
        'filter.gl_journal_cash_disbursement' => ['Cash Disbursement', 'Pengeluaran Kas'],
    ];

    foreach ($expected as $key => [$enText, $idText]) {
        expect(issue599LocaleLookup($en, $key))->toBe($enText, "en {$key}")
            ->and(issue599LocaleLookup($id, $key))->toBe($idText, "id {$key}");
    }
});

test('issue 599 sub-langkah C general ledger filter file no longer hardcodes english labels', function () {
    $source = File::get(resource_path('js/components/reports/general-ledger/Filters.tsx'));

    // Literal hanya boleh muncul sebagai fallback di dalam { key, fallback }.
    expect($source)->not->toMatch("/label:\\s*'/")
        ->and($source)->not->toMatch('/placeholder="/')
        ->and($source)->not->toMatch("/(?<!fallback: )'(Journal Type|Fiscal Year|Select Type)'/");
});
