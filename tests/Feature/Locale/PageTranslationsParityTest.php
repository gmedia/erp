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
