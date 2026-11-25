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
        ->and($en['products']['title'])->toBe('Products')
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

    expect($en['assets']['title'])->toBe('Assets')
        ->and($en['assets']['description'])->toBe('Fixed asset register with cost, depreciation, and assignment.')
        ->and($en['employees']['title'])->toBe('Employees')
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

test('assets and employees page titles are explicit exceptions to the menu labels', function () {
    // Aturan (issue #462 <!-- decision --> terbaru): judul English dikunci ke nilai main,
    // bukan menu.*. Keduanya pengecualian eksplisit (judul English berbeda dengan menu).
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($en['page']['assets']['title'])->toBe('Assets')
        ->and($en['menu']['asset_data'])->toBe('Asset Data')
        ->and($en['page']['employees']['title'])->toBe('Employees')
        ->and($en['menu']['employee'])->toBe('Employee');
});

test('inventory and purchasing page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['warehouses']['title'])->toBe('Warehouses')
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
        'stock_transfers' => 'stock_transfer',
        'stock_adjustments' => 'stock_adjustment',
        'inventory_stocktakes' => 'inventory_stocktake',
        'stock_movements' => 'stock_movement',
        'purchase_requests' => 'purchase_request',
        'goods_receipts' => 'goods_receipt',
        'supplier_returns' => 'supplier_return',
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

test('warehouses and products page titles are explicit exceptions to the menu labels', function () {
    // Aturan (issue #462 <!-- decision --> terbaru): judul English dikunci ke nilai main (plural),
    // bukan menu.* (singular). Pengecualian eksplisit, tidak dilonggarkan.
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($en['page']['warehouses']['title'])->toBe('Warehouses')
        ->and($en['menu']['warehouse'])->toBe('Warehouse')
        ->and($en['page']['products']['title'])->toBe('Products')
        ->and($en['menu']['product'])->toBe('Product');
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

test('ap/ar and budget variance report page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['ap_aging']['description'])->toBe('Payables grouped by age bucket to show overdue supplier balances.')
        ->and($en['ap_outstanding']['title'])->toBe('AP Outstanding Report')
        ->and($en['ap_outstanding']['description'])->toBe('Open supplier bills that still have an unpaid balance.')
        ->and($en['ap_payment_history']['description'])->toBe('Payments made to suppliers within the selected period.')
        ->and($en['ar_aging']['description'])->toBe(
            'Receivables grouped by age bucket to show overdue customer balances.',
        )
        ->and($en['ar_outstanding']['title'])->toBe('AR Outstanding Report')
        ->and($en['ar_outstanding']['description'])->toBe('Open customer invoices that still have an unpaid balance.')
        ->and($en['customer_statement']['title'])->toBe('Customer Statement Report')
        ->and($en['customer_statement']['description'])->toBe(
            'Transaction history and outstanding balance for one customer.',
        )
        ->and($en['budget_variance']['title'])->toBe('Budget Variance Report')
        ->and($en['budget_variance']['description'])->toBe(
            'Compare actual spending against the approved budget per account.',
        );
});

test('ap/ar and budget variance report page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'ap_aging',
        'ap_outstanding',
        'ap_payment_history',
        'ar_aging',
        'ar_outstanding',
        'customer_statement',
        'budget_variance',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['ap_outstanding']['title'])->toBe('Laporan Hutang Belum Lunas')
        ->and($id['budget_variance']['title'])->toBe('Laporan Selisih Anggaran');
});

test('ap/ar and budget variance report titles follow the menu-or-exception decision', function () {
    // Aturan (issue #462 <!-- decision -->): judul memakai menu.* bila teks English sama.
    // Pengecualian eksplisit: judul English beda dengan menu, jadi tetap page.<slug>.title.
    // Judul yang English-nya sama dengan menu memakai menu.* (tidak ada kunci page.*.title).
    $menuTitles = [
        'ap_aging' => ['menu' => 'ap_aging_report', 'title' => 'AP Aging Report'],
        'ap_payment_history' => ['menu' => 'ap_payment_history_report', 'title' => 'AP Payment History'],
        'ar_aging' => ['menu' => 'ar_aging_report', 'title' => 'AR Aging Report'],
    ];
    // Pengecualian eksplisit: judul English berbeda dengan menu, tetap page.<slug>.title.
    $exceptions = [
        'ap_outstanding' => 'ap_outstanding_report',
        'ar_outstanding' => 'ar_outstanding_report',
        'customer_statement' => 'customer_statement_report',
        'budget_variance' => 'budget_variance_report',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($menuTitles as $pageKey => $menuTitle) {
        expect($en['menu'][$menuTitle['menu']])->toBe($menuTitle['title']);
        expect(array_key_exists('title', $en['page'][$pageKey]))->toBeFalse();
    }

    foreach ($exceptions as $pageKey => $menuKey) {
        expect($en['page'][$pageKey]['title'])->not->toBe(
            $en['menu'][$menuKey],
            "page.{$pageKey}.title equals menu.{$menuKey}; move it out of the exception list",
        );
    }
});

test('inventory and purchasing report group page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['inventory_valuation']['title'])->toBe('Inventory Valuation Report')
        ->and($en['inventory_valuation']['description'])->toBe('Stock quantity and value per product and warehouse.')
        ->and($en['stock_movement']['title'])->toBe('Stock Movement Report')
        ->and($en['stock_movement']['description'])->toBe('Stock in and out per product, with source document.')
        ->and($en['stock_adjustment']['title'])->toBe('Stock Adjustment Report')
        ->and($en['stock_adjustment']['description'])->toBe('Stock adjustments that changed quantities, with reason.')
        ->and($en['inventory_stocktake_variance']['title'])->toBe('Inventory Stocktake Variance Report')
        ->and($en['inventory_stocktake_variance']['description'])->toBe(
            'Differences between system stock and physical stocktake counts.',
        )
        ->and($en['purchase_order_status']['title'])->toBe('Purchase Order Status Report')
        ->and($en['purchase_order_status']['description'])->toBe(
            'Progress of purchase orders from issue to receipt and billing.',
        )
        ->and($en['purchase_history']['title'])->toBe('Purchase History Report')
        ->and($en['purchase_history']['description'])->toBe('Purchase transactions with supplier and product detail.')
        ->and($en['goods_receipt']['title'])->toBe('Goods Receipt Report')
        ->and($en['goods_receipt']['description'])->toBe('Goods received from suppliers against purchase orders.');
});

test('inventory and purchasing report group page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'inventory_valuation',
        'stock_movement',
        'stock_adjustment',
        'inventory_stocktake_variance',
        'purchase_order_status',
        'purchase_history',
        'goods_receipt',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['inventory_valuation']['title'])->toBe('Laporan Nilai Persediaan')
        ->and($id['goods_receipt']['title'])->toBe('Laporan Penerimaan Barang');
});

test('inventory and purchasing report titles follow the menu-or-exception decision', function () {
    // Aturan (issue #462 <!-- decision -->): judul memakai menu.* bila teks English sama.
    // Semua judul di grup ini English-nya beda dengan menu (mis. "Stock Movement Report" vs "Stock Movement"),
    // jadi seluruhnya masuk pengecualian dan tetap page.<slug>.title.
    $exceptions = [
        'inventory_valuation' => 'inventory_valuation_report',
        'stock_movement' => 'stock_movement_report',
        'stock_adjustment' => 'stock_adjustment_report',
        'inventory_stocktake_variance' => 'inventory_stocktake_variance_report',
        'purchase_order_status' => 'purchase_order_status_report',
        'purchase_history' => 'purchase_history_report',
        'goods_receipt' => 'goods_receipt_report',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($exceptions as $pageKey => $menuKey) {
        expect($en['page'][$pageKey]['title'])->not->toBe(
            $en['menu'][$menuKey],
            "page.{$pageKey}.title equals menu.{$menuKey}; move it out of the exception list",
        );
    }
});

test('asset report and dashboard group page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['assets_register']['title'])->toBe('Asset Register Report')
        ->and($en['assets_register']['description'])->toBe(
            'Fixed asset list with acquisition, location, and current status.',
        )
        ->and($en['book_value_depreciation']['title'])->toBe('Book Value & Depreciation Report')
        ->and($en['book_value_depreciation']['description'])->toBe(
            'Acquisition cost, accumulated depreciation, and book value per asset.',
        )
        ->and($en['maintenance_cost']['title'])->toBe('Maintenance Cost Report')
        ->and($en['maintenance_cost']['description'])->toBe(
            'Maintenance spending per asset over the selected period.',
        )
        ->and($en['asset_stocktake_variances']['title'])->toBe('Stocktake Variance Report')
        ->and($en['asset_stocktake_variances']['description'])->toBe(
            'Differences between recorded and physically counted assets.',
        )
        ->and($en['asset_dashboard']['title'])->toBe('Asset Management Overview')
        ->and($en['asset_dashboard']['description'])->toBe(
            "Monitor the status, value, and health of your organization's assets.",
        )
        ->and($en['dashboard']['description'])->toBe(
            'At-a-glance entity counts, operational shortcuts, and master-data mix. Financial KPIs stay on Financial Dashboard.',
        );
});

test('asset report and dashboard group page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'assets_register',
        'book_value_depreciation',
        'maintenance_cost',
        'asset_stocktake_variances',
        'asset_dashboard',
        'dashboard',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['assets_register']['title'])->toBe('Laporan Register Aset');
});

test('asset report page titles are explicit exceptions to the menu labels', function () {
    // Aturan (issue #462 <!-- decision -->): judul English berbeda dengan menu, jadi tetap page.<slug>.title.
    $exceptions = [
        'assets_register' => 'asset_register_report',
        'book_value_depreciation' => 'book_value_depreciation_report',
        'maintenance_cost' => 'maintenance_cost_report',
        'asset_stocktake_variances' => 'stocktake_variance_report',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($exceptions as $pageKey => $menuKey) {
        expect($en['page'][$pageKey]['title'])->not->toBe(
            $en['menu'][$menuKey],
            "page.{$pageKey}.title equals menu.{$menuKey}; move it out of the exception list",
        );
    }
});

test('approval and pipeline group page strings keep the english labels', function () {
    $en = localePageTranslations('en.json');

    expect($en['approval_flows']['title'])->toBe('Approval Flows')
        ->and($en['approval_flows']['description'])->toBe('Approval definitions with ordered steps and approvers.')
        ->and($en['approval_delegations']['title'])->toBe('Approval Delegations')
        ->and($en['approval_delegations']['description'])->toBe(
            'Delegations that let another user approve on your behalf for a period.',
        )
        ->and($en['pipelines']['title'])->toBe('Pipelines')
        ->and($en['pipelines']['description'])->toBe('Workflow pipelines and the states entities move through.')
        ->and($en['pipeline_dashboard']['description'])->toBe(
            'Monitor state distributions and identify bottlenecks across your active pipelines.',
        )
        ->and($en['my_approvals']['description'])->toBe(
            'Review and act on documents waiting for your decision.',
        )
        ->and($en['approval_monitoring']['description'])->toBe('Monitor approval workflows and overdue tasks.')
        ->and($en['approval_audit_trail']['description'])->toBe('History of approval actions taken on requests.')
        ->and($en['pipeline_audit_trail']['description'])->toBe('History of state transitions on pipeline records.');
});

test('approval and pipeline group page strings are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'approval_flows',
        'approval_delegations',
        'pipelines',
        'pipeline_dashboard',
        'my_approvals',
        'approval_monitoring',
        'approval_audit_trail',
        'pipeline_audit_trail',
    ];

    foreach ($modules as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])->not->toBe($en[$module][$field], "page.{$module}.{$field} not translated");
        }
    }

    expect($id['approval_flows']['title'])->toBe('Alur Persetujuan')
        ->and($id['approval_delegations']['title'])->toBe('Delegasi Persetujuan')
        ->and($id['pipelines']['title'])->toBe('Pipeline');
});

test('approval and pipeline group page titles follow the menu-or-exception decision', function () {
    // Aturan (issue #462 <!-- decision -->): judul memakai menu.* bila teks English sama.
    // Judul yang English-nya sama dengan menu tidak punya kunci page.*.title (memakai menu.*).
    $menuTitles = [
        'my_approvals' => 'my_approvals',
        'approval_monitoring' => 'approval_monitoring',
        'approval_audit_trail' => 'approval_audit_trail',
        'pipeline_dashboard' => 'pipeline_dashboard',
        'pipeline_audit_trail' => 'pipeline_audit_trail',
    ];
    // Pengecualian eksplisit: judul English berbeda dengan menu, tetap page.<slug>.title.
    $exceptions = [
        'approval_flows' => 'approval_flow',
        'approval_delegations' => 'approval_delegation',
        'pipelines' => 'pipeline',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($menuTitles as $pageKey => $menuKey) {
        expect(array_key_exists('title', $en['page'][$pageKey] ?? []))->toBeFalse("page.{$pageKey}.title must use menu.*");
        expect($en['menu'])->toHaveKey($menuKey);
    }

    foreach ($exceptions as $pageKey => $menuKey) {
        expect($en['page'][$pageKey]['title'])->not->toBe(
            $en['menu'][$menuKey],
            "page.{$pageKey}.title equals menu.{$menuKey}; move it out of the exception list",
        );
    }
});

test('assets and employees search placeholders keep english and are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    expect($en['assets']['search_placeholder'])->toBe('Search assets...')
        ->and($en['employees']['search_placeholder'])->toBe('Search employees...')
        ->and($id['assets']['search_placeholder'])->toBe('Cari aset...')
        ->and($id['employees']['search_placeholder'])->toBe('Cari karyawan...');
});

test('search placeholders keep the english text of main per page', function () {
    // Aturan (issue #462 <!-- decision --> 2026-11-25T12:51Z): teks English placeholder pencarian
    // harus PERSIS sama dengan origin/main. Daftar eksplisit; id boleh diterjemahkan bebas.
    $expected = [
        'assets' => 'Search assets...',
        'employees' => 'Search employees...',
        'journal_entries' => 'Search entry...',
        'stock_monitor' => 'Search product, category, warehouse, branch...',
    ];
    $expectedFilter = [
        'products' => 'Search code, name...',
        'purchase_orders' => 'Search PO number, payment terms, notes, or shipping address...',
    ];

    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    foreach ($expected as $page => $placeholder) {
        expect($en[$page]['search_placeholder'])->toBe($placeholder, "en page.{$page}.search_placeholder drifted from main");
        expect($id[$page]['search_placeholder'])->not->toBe($placeholder, "page.{$page}.search_placeholder not translated");
    }

    foreach ($expectedFilter as $page => $placeholder) {
        expect($en[$page]['filter_search_placeholder'])->toBe($placeholder, "en page.{$page}.filter_search_placeholder drifted from main");
        expect($id[$page]['filter_search_placeholder'])->not->toBe($placeholder, "page.{$page}.filter_search_placeholder not translated");
    }
});

test('products and purchase orders filter placeholders keep english and are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    expect($en['products']['filter_search_placeholder'])->toBe('Search code, name...')
        ->and($en['purchase_orders']['filter_search_placeholder'])->toBe(
            'Search PO number, payment terms, notes, or shipping address...',
        )
        ->and($id['products']['filter_search_placeholder'])->toBe('Cari kode, nama...')
        ->and($id['purchase_orders']['filter_search_placeholder'])->not->toBe(
            $en['purchase_orders']['filter_search_placeholder'],
        );
});

test('journal entries and stock monitor search placeholders are translated to indonesian', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    expect($en['journal_entries']['search_placeholder'])->toBe('Search entry...')
        ->and($id['journal_entries']['search_placeholder'])->toBe('Cari jurnal...')
        ->and($en['stock_monitor']['search_placeholder'])->toBe('Search product, category, warehouse, branch...')
        ->and($id['stock_monitor']['search_placeholder'])->toBe('Cari produk, kategori, gudang, cabang...');
});

test('asset statuses have translated status labels in both locales', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR)['status'];
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR)['status'];

    foreach (['active', 'disposed', 'draft', 'lost', 'maintenance'] as $status) {
        expect($en[$status])->toBeString()->not->toBeEmpty()
            ->and($id[$status])->toBeString()->not->toBeEmpty();
    }

    expect($en['disposed'])->toBe('Disposed')
        ->and($id['disposed'])->toBe('Dihapus Buku')
        ->and($en['maintenance'])->toBe('Maintenance')
        ->and($id['maintenance'])->toBe('Perawatan');
});

test('ap aging report title uses the sidebar menu label in both locales', function () {
    foreach (['en.json', 'id.json'] as $file) {
        $menu = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR)['menu'];

        expect($menu['ap_aging_report'])->not->toBeEmpty();
    }

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($en['menu']['ap_aging_report'])->toBe('AP Aging Report')
        ->and($id['menu']['ap_aging_report'])->toBe('Laporan Umur Hutang')
        ->and($en['page']['ap_aging']['description'])->toBe(
            'Payables grouped by age bucket to show overdue supplier balances.',
        )
        ->and($id['page']['ap_aging']['description'])->not->toBe($en['page']['ap_aging']['description']);
});

test('stock movements breadcrumb group label uses the inventory menu key in both locales', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($en['menu']['inventory'])->toBe('Inventory')
        ->and($id['menu']['inventory'])->toBe('Persediaan')
        ->and($id['menu']['inventory'])->not->toBe($en['menu']['inventory'])
        ->and($en['page']['stock_movements']['title'])->toBe('Stock Movements');
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

test('report configurations page title and description are translated in both locales', function () {
    $en = localePageTranslations('en.json')['report_configurations'];
    $id = localePageTranslations('id.json')['report_configurations'];

    expect($en['title'])->toBe('Report Configurations')
        ->and($en['description'])->toBe('Settings that define how each report is generated and displayed.')
        ->and($id['title'])->toBe('Konfigurasi Laporan')
        ->and($id['title'])->not->toBe($en['title'])
        ->and($id['description'])->not->toBe($en['description']);
});

test('breadcrumb titles that match page titles reuse the page translation keys', function () {
    $en = localePageTranslations('en.json');
    $id = localePageTranslations('id.json');

    $modules = [
        'customers', 'suppliers', 'asset_categories', 'product_categories', 'units',
        'asset_models', 'asset_locations', 'asset_movements', 'asset_maintenances',
        'asset_stocktakes', 'pipelines', 'approval_flows', 'approval_delegations',
    ];

    foreach ($modules as $module) {
        expect($en[$module]['title'])->not->toBeEmpty()
            ->and($id[$module]['title'])->not->toBeEmpty();
    }

    expect($en['customers']['title'])->toBe('Customers')
        ->and($en['asset_maintenances']['title'])->toBe('Asset Maintenances')
        ->and($en['approval_delegations']['title'])->toBe('Approval Delegations')
        ->and($id['customers']['title'])->toBe('Pelanggan');
});

test('six redesigned page titles keep the english text of main and are menu-label exceptions', function () {
    // Judul English dikunci ke teks main (plural). Daftar eksplisit: hanya halaman yang
    // diubah PR #487 ini. Judul berbeda dengan menu.* (singular), jadi tetap page.<slug>.title.
    $expected = [
        'warehouses' => ['title' => 'Warehouses', 'menu' => 'warehouse'],
        'employees' => ['title' => 'Employees', 'menu' => 'employee'],
        'products' => ['title' => 'Products', 'menu' => 'product'],
        'fiscal_years' => ['title' => 'Fiscal Years', 'menu' => 'fiscal_year'],
        'coa_versions' => ['title' => 'COA Versions', 'menu' => 'coa_version'],
        'assets' => ['title' => 'Assets', 'menu' => 'asset_data'],
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    foreach ($expected as $pageKey => $spec) {
        expect($en['page'][$pageKey]['title'])->toBe($spec['title'], "en page.{$pageKey}.title drifted from main");
        expect($en['menu'][$spec['menu']])->not->toBe(
            $spec['title'],
            "menu.{$spec['menu']} now equals page title; drop exception",
        );
        expect($id['page'][$pageKey]['title'])->not->toBe($spec['title'], "page.{$pageKey}.title not translated");
    }
});

test('six redesigned page breadcrumbs use the english page title of main', function () {
    // Breadcrumb English keenam halaman harus sama dengan judul English main.
    $configs = File::get(resource_path('js/utils/entityConfigs.ts'));

    $breadcrumbs = [
        'Warehouses' => 'page.warehouses.title',
        'Employees' => 'page.employees.title',
        'Products' => 'page.products.title',
        'Fiscal Years' => 'page.fiscal_years.title',
        'COA Versions' => 'page.coa_versions.title',
        'Assets' => 'page.assets.title',
    ];

    foreach ($breadcrumbs as $title => $key) {
        expect(preg_match("/title: '{$title}',\\s*titleKey: '{$key}',/", $configs))->toBe(
            1,
            "breadcrumb {$title} must use {$key}",
        );
    }
});
