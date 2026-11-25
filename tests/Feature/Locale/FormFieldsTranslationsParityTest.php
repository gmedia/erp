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

test('purchase request and goods receipt form labels keep the english text', function () {
    $en = localeFormFieldTranslations('en.json');

    expect($en['purchase_requests']['add_title'])->toBe('Add New Purchase Request')
        ->and($en['purchase_requests']['edit_title'])->toBe('Edit Purchase Request')
        ->and($en['purchase_requests']['pr_number'])->toBe('PR Number')
        ->and($en['purchase_requests']['priority_urgent'])->toBe('Urgent')
        ->and($en['purchase_requests']['status_pending_approval'])->toBe('Pending Approval')
        ->and($en['purchase_requests']['item_estimated_unit_price'])->toBe('Est. Unit Price')
        ->and($en['goods_receipts']['add_title'])->toBe('Add New Goods Receipt')
        ->and($en['goods_receipts']['edit_title'])->toBe('Edit Goods Receipt')
        ->and($en['goods_receipts']['gr_number'])->toBe('GR Number')
        ->and($en['goods_receipts']['supplier_delivery_note'])->toBe('Supplier Delivery Note')
        ->and($en['goods_receipts']['item_qty_received'])->toBe('Qty Received')
        ->and($en['goods_receipts']['item_qty_accepted'])->toBe('Qty Accepted')
        ->and($en['goods_receipts']['item_qty_rejected'])->toBe('Qty Rejected');
});

test('purchase request and goods receipt form labels are translated to indonesian', function () {
    $en = localeFormFieldTranslations('en.json');
    $id = localeFormFieldTranslations('id.json');

    foreach (['purchase_requests', 'goods_receipts'] as $module) {
        foreach (array_keys($en[$module]) as $field) {
            expect($id[$module][$field])
                ->not->toBe('', "form_fields.{$module}.{$field} empty");
        }
    }

    expect($id['purchase_requests']['add_title'])->toBe('Tambah Permintaan Pembelian Baru')
        ->and($id['purchase_requests']['status_approved'])->toBe('Disetujui')
        ->and($id['goods_receipts']['add_title'])->toBe('Tambah Penerimaan Barang Baru')
        ->and($id['goods_receipts']['warehouse'])->toBe('Gudang');
});
