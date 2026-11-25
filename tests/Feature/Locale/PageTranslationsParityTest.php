<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function localePageTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['page'] ?? [];
}

test('page translation keys exist in both english and indonesian locales', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_keys($en))->toEqual(array_keys($id));

    foreach ($en as $module => $strings) {
        expect(array_keys($strings))->toEqual(array_keys($id[$module]), "page.{$module} keys differ");
    }
});

test('purchase orders and products page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['purchase_orders']['title'])->toBe('Purchase Orders')
        ->and($en['purchase_orders']['description'])->toBe(
            'Orders sent to suppliers with receipt and billing progress.',
        )
        ->and($en['purchase_orders']['search_placeholder'])->toBe('Search purchase orders...')
        ->and($en['products']['title'])->toBe('Product')
        ->and($en['products']['description'])->toBe(
            'Products, materials, and services with pricing and stock settings.',
        )
        ->and($en['products']['search_placeholder'])->toBe('Search products...');
});

test('purchase orders and products page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    foreach (['purchase_orders', 'products'] as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['purchase_orders']['title'])->toBe('Pesanan Pembelian')
        ->and($id['products']['title'])->toBe('Produk');
});

test('journal entries and stock monitor page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['journal_entries']['title'])->toBe('Journal Entries')
        ->and($en['journal_entries']['description'])->toBe('Manual journal entries with debit and credit lines.')
        ->and($en['journal_entries']['search_placeholder'])->toBe('Search entry...')
        ->and($en['stock_monitor']['title'])->toBe('Stock Monitor')
        ->and($en['stock_monitor']['description'])->toBe(
            'Stock on hand, value, and low-stock items per product and warehouse.',
        )
        ->and($en['stock_monitor']['search_placeholder'])->toBe(
            'Search product, category, warehouse, branch...',
        );
});

test('journal entries and stock monitor page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    foreach (['journal_entries', 'stock_monitor'] as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['journal_entries']['title'])->toBe('Jurnal Umum')
        ->and($id['stock_monitor']['title'])->toBe('Pemantauan Stok');
});

test('assets and employees page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['assets']['title'])->toBe('Asset Data')
        ->and($en['assets']['description'])->toBe('Fixed asset register with cost, depreciation, and assignment.')
        ->and($en['employees']['title'])->toBe('Employee')
        ->and($en['employees']['description'])->toBe(
            'Staff records with employment, department, and position history.',
        );
});

test('assets and employees page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    foreach (['assets', 'employees'] as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['assets']['title'])->toBe('Data Aset')
        ->and($id['employees']['title'])->toBe('Karyawan');
});

test('assets and employees page titles match the sidebar menu labels', function () {
    foreach (['en.json', 'id.json'] as $file) {
        $menu = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR)['menu'];
        $page = localePageTranslations($file);

        expect($page['assets']['title'])->toBe($menu['asset_data'])
            ->and($page['employees']['title'])->toBe($menu['employee']);
    }
});

test('inventory and purchasing page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['warehouses']['title'])->toBe('Warehouse')
        ->and($en['warehouses']['description'])->toBe('Storage locations that hold product stock.')
        ->and($en['stock_transfers']['title'])->toBe('Stock Transfers')
        ->and($en['stock_transfers']['description'])->toBe(
            'Move stock between warehouses with approval and receipt tracking.',
        )
        ->and($en['stock_adjustments']['title'])->toBe('Stock Adjustments')
        ->and($en['stock_adjustments']['description'])->toBe(
            'Record stock corrections with approval and journal posting.',
        )
        ->and($en['inventory_stocktakes']['title'])->toBe('Inventory Stocktakes')
        ->and($en['inventory_stocktakes']['description'])->toBe(
            'Physical counts of warehouse stock with variance results.',
        )
        ->and($en['stock_movements']['title'])->toBe('Stock Movements')
        ->and($en['stock_movements']['description'])->toBe(
            'Digital stock card — product movements by warehouse, type, and period.',
        )
        ->and($en['purchase_requests']['title'])->toBe('Purchase Requests')
        ->and($en['purchase_requests']['description'])->toBe(
            'Internal requests for goods before a purchase order is raised.',
        )
        ->and($en['goods_receipts']['title'])->toBe('Goods Receipts')
        ->and($en['goods_receipts']['description'])->toBe('Record received goods and update warehouse stock.')
        ->and($en['supplier_returns']['title'])->toBe('Supplier Returns')
        ->and($en['supplier_returns']['description'])->toBe(
            'Return received goods to suppliers with stock deduction.',
        );
});

test('inventory and purchasing page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'warehouses',
        'stock_transfers',
        'stock_adjustments',
        'inventory_stocktakes',
        'stock_movements',
        'purchase_requests',
        'goods_receipts',
        'supplier_returns',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['warehouses']['title'])->toBe('Gudang')
        ->and($id['stock_transfers']['title'])->toBe('Transfer Stok')
        ->and($id['goods_receipts']['title'])->toBe('Penerimaan Barang');
});

test('inventory and purchasing page titles match the sidebar menu labels', function () {
    $pairs = [
        'warehouses' => 'warehouse',
        'stock_transfers' => 'stock_transfer',
        'stock_adjustments' => 'stock_adjustment',
        'inventory_stocktakes' => 'inventory_stocktake',
        'stock_movements' => 'stock_movement',
        'purchase_requests' => 'purchase_request',
        'goods_receipts' => 'goods_receipt',
        'supplier_returns' => 'supplier_return',
        'products' => 'product',
        'purchase_orders' => 'purchase_order',
        'stock_monitor' => 'stock_monitor',
    ];

    foreach (['en.json', 'id.json'] as $file) {
        $menu = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR)['menu'];
        $page = localePageTranslations($file);

        foreach ($pairs as $pageKey => $menuKey) {
            expect($page[$pageKey]['title'])->toBe($menu[$menuKey], "page.{$pageKey}.title != menu.{$menuKey}");
        }
    }
});

test('asset column labels keep english text and are translated to indonesian', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR)['column'];
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR)['column'];

    $keys = ['location', 'employee', 'purchase_cost', 'purchase_date'];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key], "column.{$key} not translated");
    }

    expect($en['purchase_cost'])->toBe('Cost')
        ->and($en['purchase_date'])->toBe('Purchase Date')
        ->and($id['purchase_date'])->toBe('Tanggal Perolehan');
});

test('accounting and ap/ar page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['accounts']['title'])->toBe('Chart of Accounts')
        ->and($en['accounts']['description'])->toBe('Manage hierarchical accounts and COA versions.')
        ->and($en['journal_entries']['description'])->toBe('Manual journal entries with debit and credit lines.')
        ->and($en['posting_journals']['title'])->toBe('Posting Journals')
        ->and($en['posting_journals']['description'])->toBe(
            'Review draft journal entries and post them to the general ledger.',
        )
        ->and($en['fiscal_years']['description'])->toBe(
            'Accounting periods that scope journals and financial reports.',
        )
        ->and($en['coa_versions']['description'])->toBe('Chart of accounts versions per fiscal year.')
        ->and($en['bank_reconciliations']['description'])->toBe(
            'Match bank statement balances with the books for each account.',
        )
        ->and($en['credit_notes']['description'])->toBe('Credit notes that reduce customer invoice balances.');
});

test('accounting and ap/ar page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'accounts',
        'journal_entries',
        'posting_journals',
        'fiscal_years',
        'coa_versions',
        'account_mappings',
        'budgets',
        'bank_reconciliations',
        'recurring_journals',
        'period_closings',
        'supplier_bills',
        'ap_payments',
        'customer_invoices',
        'ar_receipts',
        'credit_notes',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['accounts']['title'])->toBe('Bagan Akun')
        ->and($id['journal_entries']['title'])->toBe('Jurnal Umum')
        ->and($id['ap_payments']['title'])->toBe('Pembayaran Hutang');
});

test('accounting and ap/ar page titles match the sidebar menu labels', function () {
    $pairs = [
        'accounts' => 'chart_of_accounts',
        'posting_journals' => 'posting_journal',
        'fiscal_years' => 'fiscal_year',
        'coa_versions' => 'coa_version',
        'account_mappings' => 'account_mapping',
        'budgets' => 'budget',
        'bank_reconciliations' => 'bank_reconciliation',
        'recurring_journals' => 'recurring_journal',
        'period_closings' => 'period_closing',
        'supplier_bills' => 'supplier_bill',
        'ap_payments' => 'ap_payment',
        'customer_invoices' => 'customer_invoice',
        'ar_receipts' => 'ar_receipt',
        'credit_notes' => 'credit_note',
    ];

    foreach (['en.json', 'id.json'] as $file) {
        $menu = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR)['menu'];
        $page = localePageTranslations($file);

        foreach ($pairs as $pageKey => $menuKey) {
            expect($page[$pageKey]['title'])->toBe($menu[$menuKey], "page.{$pageKey}.title != menu.{$menuKey}");
        }
    }
});

test('master data and asset page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['customers']['title'])->toBe('Customers')
        ->and($en['customers']['description'])->toBe('Customer master data with billing and contact details.')
        ->and($en['suppliers']['title'])->toBe('Suppliers')
        ->and($en['suppliers']['description'])->toBe('Vendor master data used by purchasing and payables.')
        ->and($en['departments']['title'])->toBe('Departments')
        ->and($en['departments']['description'])->toBe('Organizational units used to group employees and assets.')
        ->and($en['positions']['title'])->toBe('Positions')
        ->and($en['positions']['description'])->toBe('Job titles assignable to employees.')
        ->and($en['branches']['title'])->toBe('Branches')
        ->and($en['branches']['description'])->toBe('Company branches used across transactions and reports.')
        ->and($en['customer_categories']['title'])->toBe('Customer Categories')
        ->and($en['customer_categories']['description'])->toBe('Categories used to group customers.')
        ->and($en['supplier_categories']['title'])->toBe('Supplier Categories')
        ->and($en['supplier_categories']['description'])->toBe('Categories used to group suppliers.')
        ->and($en['product_categories']['title'])->toBe('Product Categories')
        ->and($en['product_categories']['description'])->toBe('Categories used to classify products and materials.')
        ->and($en['units']['title'])->toBe('Units')
        ->and($en['units']['description'])->toBe('Units of measure used by products and transactions.')
        ->and($en['asset_categories']['title'])->toBe('Asset Categories')
        ->and($en['asset_categories']['description'])->toBe('Categories used to classify fixed assets.')
        ->and($en['asset_locations']['title'])->toBe('Asset Locations')
        ->and($en['asset_locations']['description'])->toBe('Physical locations within branches for asset placement.')
        ->and($en['asset_models']['title'])->toBe('Asset Models')
        ->and($en['asset_models']['description'])->toBe('Manufacturer models used as templates for assets.')
        ->and($en['asset_movements']['title'])->toBe('Asset Movements')
        ->and($en['asset_movements']['description'])->toBe('Transfer and assignment history of fixed assets.')
        ->and($en['asset_maintenances']['title'])->toBe('Asset Maintenances')
        ->and($en['asset_maintenances']['description'])->toBe(
            'Scheduled and performed maintenance for fixed assets.',
        )
        ->and($en['asset_stocktakes']['title'])->toBe('Asset Stocktakes')
        ->and($en['asset_stocktakes']['description'])->toBe('Physical verification of fixed assets per branch.')
        ->and($en['asset_depreciation_runs']['title'])->toBe('Asset Depreciation Runs')
        ->and($en['asset_depreciation_runs']['description'])->toBe(
            'Calculate and review asset depreciation. Once verified, post the calculated amounts to the general ledger.',
        );
});

test('master data and asset page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'customers',
        'suppliers',
        'departments',
        'positions',
        'branches',
        'customer_categories',
        'supplier_categories',
        'product_categories',
        'units',
        'asset_categories',
        'asset_locations',
        'asset_models',
        'asset_movements',
        'asset_maintenances',
        'asset_stocktakes',
        'asset_depreciation_runs',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['customers']['title'])->toBe('Pelanggan')
        ->and($id['asset_depreciation_runs']['title'])->toBe('Penyusutan Aset');
});

test('master data and asset page titles follow the menu-or-exception decision', function () {
    // Aturan (issue #462 <!-- decision -->): judul memakai menu.* hanya bila teks English sama.
    // Daftar di bawah adalah PENGECUALIAN EKSPLISIT: judul English beda dengan menu, jadi tetap page.<slug>.title.
    $exceptions = [
        'customers' => 'customer',
        'suppliers' => 'supplier',
        'departments' => 'department',
        'positions' => 'position',
        'branches' => 'branch',
        'customer_categories' => 'customer_category',
        'supplier_categories' => 'supplier_category',
        'product_categories' => 'product_category',
        'units' => 'unit',
        'asset_categories' => 'asset_category',
        'asset_locations' => 'asset_location',
        'asset_models' => 'asset_model',
        'asset_movements' => 'asset_movement',
        'asset_maintenances' => 'asset_maintenance',
        'asset_stocktakes' => 'asset_stocktake',
        'asset_depreciation_runs' => 'asset_depreciation_run',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($exceptions as $pageKey => $menuKey) {
        expect($en['page'][$pageKey]['title'])->not->toBe(
            $en['menu'][$menuKey],
            "page.{$pageKey}.title equals menu.{$menuKey}; move it out of the exception list",
        );
    }

    foreach (['en.json', 'id.json'] as $file) {
        $menu = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR)['menu'];
        $page = localePageTranslations($file);

        foreach ($exceptions as $pageKey => $menuKey) {
            expect(array_key_exists('title', $page[$pageKey]))->toBeTrue("page.{$pageKey}.title missing");
            expect(array_key_exists($menuKey, $menu))->toBeTrue("menu.{$menuKey} missing");
        }
    }
});

test('reports breadcrumb group label is translated in both locales', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR)['reports'];
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR)['reports'];

    expect($en['breadcrumbGroup'])->toBe('Reports')
        ->and($id['breadcrumbGroup'])->toBe('Laporan');
});

test('financial report group page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['balance_sheet']['description'])->toBe(
        'Financial position at a point in time: assets, liabilities, and equity.',
    )
        ->and($en['income_statement']['description'])->toBe(
            'Revenue and expenses over a period to show net income.',
        )
        ->and($en['cash_flow']['description'])->toBe(
            'Cash movements grouped into operating, investing, and financing activities.',
        )
        ->and($en['comparative']['title'])->toBe('Comparative Report')
        ->and($en['comparative']['description'])->toBe(
            'Compare assets, liabilities, equity, revenue, and expenses across fiscal years.',
        )
        ->and($en['trial_balance']['description'])->toBe(
            'Debit and credit totals of every account to check that the books balance.',
        )
        ->and($en['trial_balance_detailed']['description'])->toBe(
            'Trial balance with opening, movement, and closing balance per account.',
        )
        ->and($en['general_ledger']['title'])->toBe('General Ledger Report')
        ->and($en['general_ledger']['description'])->toBe(
            'Every journal line posted to an account within a period, with running balance.',
        )
        ->and($en['financial_dashboard']['description'])->toBe(
            'Monitor key financial metrics, cash flow, and expense trends.',
        )
        ->and($en['aging_dashboard']['description'])->toBe(
            'Monitor outstanding receivables and payables bucketed by overdue age.',
        );
});

test('financial report group page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'balance_sheet',
        'income_statement',
        'cash_flow',
        'comparative',
        'trial_balance',
        'trial_balance_detailed',
        'general_ledger',
        'financial_dashboard',
        'aging_dashboard',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['comparative']['title'])->toBe('Laporan Perbandingan')
        ->and($id['general_ledger']['title'])->toBe('Laporan Buku Besar');
});

test('financial report group page titles follow the menu-or-exception decision', function () {
    // Aturan (issue #462 <!-- decision -->): judul memakai menu.* bila teks English sama.
    // Pengecualian eksplisit: judul English beda dengan menu, jadi tetap page.<slug>.title.
    $menuTitles = [
        'balance_sheet' => 'balance_sheet_report',
        'income_statement' => 'income_statement_report',
        'cash_flow' => 'cash_flow_report',
        'trial_balance' => 'trial_balance_report',
        'trial_balance_detailed' => 'trial_balance_detailed_report',
        'financial_dashboard' => 'financial_dashboard',
        'aging_dashboard' => 'aging_dashboard',
    ];
    $exceptions = [
        'comparative' => 'comparative_report',
        'general_ledger' => 'general_ledger_report',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($exceptions as $pageKey => $menuKey) {
        expect($en['page'][$pageKey]['title'])->not->toBe(
            $en['menu'][$menuKey],
            "page.{$pageKey}.title equals menu.{$menuKey}; move it out of the exception list",
        );
    }

    foreach (['en.json', 'id.json'] as $file) {
        $menu = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR)['menu'];
        $page = localePageTranslations($file);

        foreach ($menuTitles as $menuKey) {
            expect(array_key_exists($menuKey, $menu))->toBeTrue("menu.{$menuKey} missing");
        }

        foreach ($exceptions as $pageKey => $menuKey) {
            expect(array_key_exists('title', $page[$pageKey]))->toBeTrue("page.{$pageKey}.title missing");
            expect(array_key_exists($menuKey, $menu))->toBeTrue("menu.{$menuKey} missing");
        }
    }

    expect($en['menu']['balance_sheet_report'])->toBe('Balance Sheet')
        ->and($en['menu']['income_statement_report'])->toBe('Income Statement')
        ->and($en['menu']['cash_flow_report'])->toBe('Cash Flow')
        ->and($en['menu']['trial_balance_report'])->toBe('Trial Balance')
        ->and($en['menu']['trial_balance_detailed_report'])->toBe('Trial Balance Detailed')
        ->and($en['menu']['financial_dashboard'])->toBe('Financial Dashboard')
        ->and($en['menu']['aging_dashboard'])->toBe('Aging Dashboard');
});

test('page translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localePageTranslations($file) as $module => $strings) {
            foreach ($strings as $field => $value) {
                expect($value)->toBeString()
                    ->and(trim($value))->not->toBe('', "{$file} page.{$module}.{$field} is empty");
            }
        }
    }
});
