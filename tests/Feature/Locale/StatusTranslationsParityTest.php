<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeStatusTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['status'] ?? [];
}

test('status translation keys exist in both english and indonesian locales', function () {
    $en = localeStatusTranslations('en.json');
    $id = localeStatusTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('english status translations keep the original english labels', function () {
    $en = localeStatusTranslations('en.json');

    expect($en['pending_approval'])->toBe('Pending Approval')
        ->and($en['partially_received'])->toBe('Partially Received')
        ->and($en['in_transit'])->toBe('In Transit')
        ->and($en['stocktake_result'])->toBe('Stocktake Result');
});

test('status translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localeStatusTranslations($file) as $key => $value) {
            expect($value)->toBeString()->and(trim($value))->not->toBe('', "{$file} status.{$key} is empty");
        }
    }
});
