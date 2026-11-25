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
