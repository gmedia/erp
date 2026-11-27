<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

test('issue 599 sub-langkah B1 filter keys exist in both locales with english fallback text', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    $expected = [
        'filter.select_priority' => ['Select Priority', 'Pilih Prioritas'],
        'filter.select_account' => ['Select Account', 'Pilih Akun'],
        'filter.select_source_coa_version' => ['Select source COA version', 'Pilih versi COA sumber'],
        'filter.select_target_coa_version' => ['Select target COA version', 'Pilih versi COA tujuan'],
        'filter.supplier_returns_select_goods_receipt' => ['Select goods receipt', 'Pilih penerimaan barang'],
        'filter.supplier_returns_select_supplier' => ['Select supplier', 'Pilih pemasok'],
        'filter.supplier_returns_select_warehouse' => ['Select warehouse', 'Pilih gudang'],
        'filter.supplier_returns_select_reason' => ['Select reason', 'Pilih alasan'],
        'filter.supplier_returns_select_status' => ['Select status', 'Pilih status'],
        'filter.recurring_select_frequency' => ['Select Frequency', 'Pilih Frekuensi'],
        'filter.recurring_frequency_daily' => ['Daily', 'Harian'],
        'filter.recurring_frequency_annual' => ['Yearly', 'Tahunan'],
        'filter.reconciliation_status_in_progress' => ['In Progress', 'Sedang Berjalan'],
        'filter.reconciliation_status_completed' => ['Completed', 'Selesai'],
        'filter.account_mapping_type_rename' => ['Rename', 'Ubah Nama'],
        'filter.account_mapping_type_split' => ['Split', 'Pecah'],
    ];

    foreach ($expected as $key => [$enText, $idText]) {
        expect(issue599LocaleLookup($en, $key))->toBe($enText, "en {$key}")
            ->and(issue599LocaleLookup($id, $key))->toBe($idText, "id {$key}");
    }
});

test('issue 599 sub-langkah B1 filter files no longer hardcode english placeholders', function () {
    $files = [
        'js/components/purchase-orders/PurchaseOrderFilters.tsx',
        'js/components/purchase-requests/PurchaseRequestFilters.tsx',
        'js/components/supplier-returns/SupplierReturnFilters.tsx',
        'js/components/recurring-journals/RecurringJournalFilters.tsx',
        'js/components/bank-reconciliations/BankReconciliationFilters.tsx',
        'js/components/account-mappings/AccountMappingFilters.tsx',
    ];

    foreach ($files as $file) {
        $source = File::get(resource_path($file));

        expect($source)->not->toMatch("/placeholder:\\s*'/", $file)
            ->and($source)->not->toMatch('/placeholder="/', $file)
            ->and($source)->not->toMatch("/createTextFilterField\\(\\s*'search',\\s*'Search'/", $file);
    }
});
