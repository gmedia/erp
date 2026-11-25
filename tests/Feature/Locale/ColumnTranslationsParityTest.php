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

test('accounting module column and filter keys have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    $keys = [
        'entry_number', 'description', 'total_amount', 'date', 'created_at',
        'fiscal_year', 'source_account', 'target_account', 'notes', 'budget_type',
        'statement_balance', 'book_balance', 'difference', 'account',
        'period_start', 'period_end', 'frequency', 'next_run_date', 'auto_post',
        'is_active', 'period_month', 'period_year', 'closing_type', 'closed_at',
        'net_income', 'source_coa_version', 'target_coa_version', 'all_types',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key]);
    }

    expect($en['entry_number'])->toBe('Entry Number')
        ->and($en['statement_balance'])->toBe('Statement Balance')
        ->and($en['net_income'])->toBe('Net Income');
});

test('ap and ar module column and filter keys have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    $keys = [
        'customer', 'payment_method', 'payment_date', 'invoice_date', 'due_date',
        'bill_date', 'credit_note_date', 'amount_due', 'unallocated',
        'payment_number', 'bill_number', 'invoice_number', 'receipt_number',
        'credit_note_number', 'payment_date_from', 'payment_date_to',
        'bill_date_from', 'bill_date_to', 'due_date_from', 'due_date_to',
        'invoice_date_from', 'invoice_date_to', 'receipt_date_from',
        'receipt_date_to', 'credit_note_date_from', 'credit_note_date_to',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key]);
    }

    expect($en['bill_number'])->toBe('Bill Number')
        ->and($en['payment_number'])->toBe('Payment Number')
        ->and($en['unallocated'])->toBe('Unallocated');
});

test('asset module column and filter keys have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    $keys = [
        'default_useful_life_months', 'parent_location', 'updated_at', 'model_name',
        'manufacturer', 'specs', 'asset', 'origin', 'destination', 'ref_notes',
        'scheduled', 'performed', 'scheduled_from', 'scheduled_to', 'performed_from',
        'performed_to', 'planned_date', 'performed_date', 'planned_from', 'planned_to',
        'perform',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key], "column.{$key} not translated");
    }

    expect($en['default_useful_life_months'])->toBe('Default Useful Life (Months)')
        ->and($en['parent_location'])->toBe('Parent Location')
        ->and($en['ref_notes'])->toBe('Ref/Notes')
        ->and($en['performed_to'])->toBe('Performed To');
});

test('master data column and filter keys exist in both locales', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    // Keys whose Indonesian text intentionally equals English (NIK, Email, Status) are checked for presence only.
    $sameInBoth = ['nik', 'email'];
    $translated = ['phone', 'salary', 'hire_date', 'position', 'symbol'];

    foreach (array_merge($sameInBoth, $translated) as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key);
    }

    foreach ($translated as $key) {
        expect($id[$key])->not->toBe($en[$key], "column.{$key} not translated");
    }

    expect($en['hire_date'])->toBe('Hire Date')
        ->and($en['symbol'])->toBe('Symbol');
});

test('asset movement type labels are translated in both locales', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR)['movement_type'];
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR)['movement_type'];

    expect(array_keys($id))->toEqual(array_keys($en));

    foreach ($en as $key => $label) {
        expect($id[$key])->not->toBe($label, "movement_type.{$key} not translated");
    }

    expect($en['transfer'])->toBe('Transfer')
        ->and($en['adjustment'])->toBe('Adjustment');
});

test('ar aging report column keys have translations', function () {
    $en = localeColumnTranslations('en.json');
    $id = localeColumnTranslations('id.json');

    $keys = [
        'invoice_number', 'customer', 'branch',
        'amount_received', 'credit_note_amount', 'amount_due', 'current',
        'days_1_30', 'days_31_60', 'days_61_90', 'days_over_90',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($id)->toHaveKey($key)
            ->and($id[$key])->not->toBe($en[$key], "column.{$key} not translated");
    }

    // "Grand Total" and "Status" are intentionally identical in Indonesian.
    expect($en)->toHaveKey('grand_total')
        ->and($id)->toHaveKey('grand_total')
        ->and($en['status'])->toBe('Status')
        ->and($en['amount_received'])->toBe('Amount Received')
        ->and($en['credit_note_amount'])->toBe('Credit Note Amount')
        ->and($en['days_over_90'])->toBe('Over 90 Days');
});

test('column translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localeColumnTranslations($file) as $key => $value) {
            expect($value)->toBeString()->and(trim($value))->not->toBe('', "{$file} column.{$key} is empty");
        }
    }
});
