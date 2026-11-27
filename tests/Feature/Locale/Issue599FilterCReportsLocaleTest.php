<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

test('issue 599 sub-langkah C report filter keys exist in both locales with english fallback text', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    $expected = [
        'filter.select_customer' => ['Select customer', 'Pilih pelanggan'],
        'filter.select_fiscal_year' => ['Select fiscal year', 'Pilih tahun fiskal'],
        'filter.select_budget' => ['Select budget', 'Pilih anggaran'],
        'filter.select_vendor' => ['Select a vendor', 'Pilih vendor'],
        'filter.select_branch_short' => ['Select branch', 'Pilih cabang'],
        'filter.all_months' => ['All months', 'Semua bulan'],
        'filter.month_12' => ['December', 'Desember'],
        'filter.within_budget' => ['Within Budget', 'Sesuai Anggaran'],
        'filter.over_budget' => ['Over Budget', 'Melebihi Anggaran'],
        'filter.account_type_expense' => ['Expense', 'Beban'],
        'filter.bank_transfer' => ['Bank Transfer', 'Transfer Bank'],
        'filter.from' => ['From', 'Dari'],
        'filter_field.due_date_from' => ['Due Date From', 'Jatuh Tempo Dari'],
        'filter_field.payment_date_to' => ['Payment Date To', 'Tanggal Pembayaran Sampai'],
        'filter_field.as_of_date' => ['As of Date', 'Per Tanggal'],
    ];

    foreach ($expected as $key => [$enText, $idText]) {
        expect(issue599LocaleLookup($en, $key))->toBe($enText, "en {$key}")
            ->and(issue599LocaleLookup($id, $key))->toBe($idText, "id {$key}");
    }
});

test('issue 599 sub-langkah C report filter files no longer hardcode english labels', function () {
    $files = [
        'inventory-valuation', 'purchase-order-status', 'trial-balance-detailed',
        'asset-stocktake-variances', 'ar-outstanding-report', 'customer-statement-report',
        'ap-aging-report', 'ar-aging-report', 'inventory-stocktake-variance',
        'ap-payment-history-report', 'ap-outstanding-report', 'stock-adjustment',
        'budget-variance', 'goods-receipt', 'purchase-history', 'maintenance-cost',
    ];

    foreach ($files as $slug) {
        $source = File::get(resource_path("js/components/reports/{$slug}/Filters.tsx"));

        // Label/placeholder harus lewat { key, fallback }, bukan string literal polos.
        expect($source)->not->toMatch("/(?:label|placeholder):\\s*'[A-Z]/", $slug)
            ->and($source)->not->toMatch("/\\},\\s*'(?:Search|Customer|Branch|Supplier|Status|Vendor|Budget)'/", $slug);
    }
});
