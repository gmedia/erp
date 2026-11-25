<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeColumnTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['column'] ?? [];
}

test('column translation keys exist in both english and indonesian locales', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('english column translations keep the original english labels', function () {
    $en = localeColumnTranslations('en.json');

    expect($en['po_number'])->toBe('PO Number')
        ->and($en['supplier'])->toBe('Supplier')
        ->and($en['grand_total'])->toBe('Grand Total');
});

test('purchase order and request filter date labels have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    foreach (['order_date_from', 'order_date_to', 'request_date_from', 'request_date_to'] as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key]);
    }
});

test('stock transfer, adjustment and stocktake column keys have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    $keys = [
        'transfer_number', 'from_warehouse', 'to_warehouse', 'transfer_date', 'expected_arrival',
        'adjustment_number', 'adjustment_date', 'adjustment_type',
        'stocktake_number', 'stocktake_date', 'product_category',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key]);
    }

    expect($en['transfer_number'])->toBe('Transfer Number')
        ->and($en['adjustment_type'])->toBe('Adjustment Type')
        ->and($en['stocktake_number'])->toBe('Stocktake Number');
});

test('products, warehouses and stock report column keys have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    $keys = [
        'code', 'name', 'type', 'category', 'cost', 'price', 'product',
        'moved_at', 'reference', 'movement_type', 'qty_in', 'qty_out', 'balance',
        'unit_cost', 'avg_cost', 'created_by', 'qty_on_hand', 'stock_value',
        'last_movement', 'start_date', 'end_date', 'low_stock_threshold', 'search',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key]);
    }

    expect($en['moved_at'])->toBe('Moved At')
        ->and($en['qty_on_hand'])->toBe('Qty On Hand')
        ->and($en['low_stock_threshold'])->toBe('Low Stock Threshold');
});

test('column translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localeColumnTranslations($file) as $key => $value) {
            expect($value)->toBeString()->and(trim($value))->not->toBe('', "{$file} column.{$key} is empty");
        }
    }
});
