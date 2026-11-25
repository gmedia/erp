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
