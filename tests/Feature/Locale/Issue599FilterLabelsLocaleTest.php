<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

test('issue 599 budget filter keys are translated in both locales', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    expect(issue599LocaleLookup($en, 'filter.all_fiscal_years'))->toBe('All fiscal years')
        ->and(issue599LocaleLookup($id, 'filter.all_fiscal_years'))->toBe('Semua tahun fiskal')
        ->and(issue599LocaleLookup($en, 'filter.budget_type_capital'))->toBe('Capital')
        ->and(issue599LocaleLookup($id, 'filter.budget_type_capital'))->toBe('Modal')
        ->and(issue599LocaleLookup($en, 'filter.budget_type_revenue'))->toBe('Revenue')
        ->and(issue599LocaleLookup($id, 'filter.budget_type_revenue'))->toBe('Pendapatan');
});

test('issue 599 period closing filter keys are translated in both locales', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    expect(issue599LocaleLookup($en, 'filter.period_select_fiscal_year'))->toBe('Select Fiscal Year')
        ->and(issue599LocaleLookup($id, 'filter.period_select_fiscal_year'))->toBe('Pilih Tahun Fiskal')
        ->and(issue599LocaleLookup($en, 'filter.period_closing_type_annual'))->toBe('Annual')
        ->and(issue599LocaleLookup($id, 'filter.period_closing_type_annual'))->toBe('Tahunan');
});

test('issue 599 goods receipt filter keys are translated in both locales', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    expect(issue599LocaleLookup($en, 'filter.receipt_date_from'))->toBe('Receipt Date From')
        ->and(issue599LocaleLookup($id, 'filter.receipt_date_from'))->toBe('Tanggal Penerimaan Dari')
        ->and(issue599LocaleLookup($en, 'filter.receipt_date_to'))->toBe('Receipt Date To')
        ->and(issue599LocaleLookup($id, 'filter.receipt_date_to'))->toBe('Tanggal Penerimaan Sampai')
        ->and(issue599LocaleLookup($en, 'filter.select_status_title'))->toBe('Select Status')
        ->and(issue599LocaleLookup($id, 'filter.select_status_title'))->toBe('Pilih Status');
});

test('issue 599 migrated filter files no longer hardcode english labels', function () {
    $goods = File::get(resource_path('js/components/goods-receipts/GoodsReceiptFilters.tsx'));
    $stocktake = File::get(resource_path('js/components/inventory-stocktakes/InventoryStocktakeFilters.tsx'));

    // Teks en tetap ada sebagai fallback { key, fallback }; yang dilarang literal polos (argumen/label).
    expect($goods)->not->toContain("label: 'Receipt Date From'")
        ->and($goods)->not->toContain("placeholder: 'Receipt Date From'")
        ->and($goods)->not->toMatch("/^\\s*'Select a warehouse',\\s*$/m")
        ->and($goods)->not->toMatch("/^\\s*'Select Status',\\s*$/m")
        ->and($stocktake)->not->toContain("createTextFilterField('search', 'Search'");
});

test('issue 599 migrated budget and period closing filter files no longer hardcode english labels', function () {
    $budget = File::get(resource_path('js/components/budgets/BudgetFilters.tsx'));
    $period = File::get(resource_path('js/components/period-closings/PeriodClosingFilters.tsx'));

    // Placeholder/label literal tidak boleh lagi (hanya boleh sebagai fallback di { key, fallback }).
    expect($budget)->not->toContain('placeholder="All fiscal years"')
        ->and($budget)->not->toContain("'All statuses',")
        ->and($budget)->not->toContain("'All types',")
        ->and($period)->not->toContain("'Select Status',")
        ->and($period)->not->toContain('placeholder="Select Fiscal Year"');
});
