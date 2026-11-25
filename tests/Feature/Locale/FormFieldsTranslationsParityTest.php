<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, array<string, string>>
 */
function localeFormFieldTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['form_fields'] ?? [];
}

test('form field translation keys exist in both english and indonesian locales', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_keys($en))->toEqual(array_keys($id));

    foreach ($en as $module => $strings) {
        expect(array_keys($strings))->toEqual(array_keys($id[$module]), "form_fields.{$module} keys differ");
    }
});

test('purchase order form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['purchase_orders']['add_title'])->toBe('Add New Purchase Order')
        ->and($en['purchase_orders']['edit_title'])->toBe('Edit Purchase Order')
        ->and($en['purchase_orders']['po_number'])->toBe('PO Number')
        ->and($en['purchase_orders']['po_number_placeholder'])->toBe('Auto-generated if empty')
        ->and($en['purchase_orders']['supplier'])->toBe('Supplier')
        ->and($en['purchase_orders']['warehouse'])->toBe('Warehouse')
        ->and($en['purchase_orders']['order_date'])->toBe('Order Date')
        ->and($en['purchase_orders']['payment_terms_placeholder'])->toBe('Net 30')
        ->and($en['purchase_orders']['item_unit_price'])->toBe('Unit Price');
});

test('purchase order form labels are translated to indonesian', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (array_keys($en['purchase_orders']) as $field) {
        expect($id['purchase_orders'][$field])
            ->not->toBe('', "form_fields.purchase_orders.{$field} empty");
    }

    expect($id['purchase_orders']['add_title'])->toBe('Tambah Pesanan Pembelian Baru')
        ->and($id['purchase_orders']['edit_title'])->toBe('Ubah Pesanan Pembelian')
        ->and($id['purchase_orders']['supplier'])->toBe('Pemasok')
        ->and($id['purchase_orders']['warehouse'])->toBe('Gudang');
});
