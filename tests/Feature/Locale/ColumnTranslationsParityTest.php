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

test('column translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localeColumnTranslations($file) as $key => $value) {
            expect($value)->toBeString()->and(trim($value))->not->toBe('', "{$file} column.{$key} is empty");
        }
    }
});
