<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function localeJson(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

function localeLookup(array $locale, string $dottedKey): ?string
{
    $node = $locale;

    foreach (explode('.', $dottedKey) as $segment) {
        if (! is_array($node) || ! array_key_exists($segment, $node)) {
            return null;
        }

        $node = $node[$segment];
    }

    return is_string($node) ? $node : null;
}

/**
 * @return list<string>
 */
function filterOptionTranslationKeys(): array
{
    $source = File::get(resource_path('js/components/common/filters.tsx'));

    preg_match_all("/(?:statusLabel|filterLabel)\\(\\s*'([^']+)'/", $source, $helperMatches);
    preg_match_all("/key: '([^']+)'/", $source, $literalMatches);

    $keys = [];

    foreach ($helperMatches[0] as $index => $match) {
        $prefix = str_starts_with($match, 'statusLabel') ? 'status' : 'filter';
        $keys[] = "{$prefix}.{$helperMatches[1][$index]}";
    }

    return array_values(array_unique([...$keys, ...$literalMatches[1]]));
}

test('every translation key used by filter options exists in both locales', function () {
    $en = localeJson('en.json');
    $id = localeJson('id.json');

    $keys = filterOptionTranslationKeys();

    expect($keys)->not->toBeEmpty();

    foreach ($keys as $key) {
        expect(localeLookup($en, $key))->not->toBeNull("en is missing {$key}");
        expect(localeLookup($id, $key))->not->toBeNull("id is missing {$key}");
    }
});

test('filter option english labels keep the text of main', function () {
    $en = localeJson('en.json');

    expect(localeLookup($en, 'status.partially_received'))->toBe('Partially Received')
        ->and(localeLookup($en, 'status.pending_approval'))->toBe('Pending Approval')
        ->and(localeLookup($en, 'status.stocktake_result'))->toBe('Stocktake Result')
        ->and(localeLookup($en, 'status.missing'))->toBe('Missing')
        ->and(localeLookup($en, 'status.surplus'))->toBe('Surplus')
        ->and(localeLookup($en, 'filter.movement_transfer_out'))->toBe('Transfer Out')
        ->and(localeLookup($en, 'filter.approval_event_step_skipped'))->toBe('Step Skipped')
        ->and(localeLookup($en, 'form_fields.stock_adjustments.adjustment_type_correction'))->toBe('Correction')
        ->and(localeLookup($en, 'form_fields.stock_adjustments.adjustment_type_initial_stock'))->toBe('Initial Stock');
});

test('filter option labels are translated to indonesian', function () {
    $en = localeJson('en.json');
    $id = localeJson('id.json');

    $keys = [
        'status.partially_received',
        'status.pending_approval',
        'status.stocktake_result',
        'status.missing',
        'status.surplus',
        'filter.movement_transfer_out',
        'filter.approval_event_step_skipped',
        'form_fields.stock_adjustments.adjustment_type_correction',
        'form_fields.stock_adjustments.adjustment_type_initial_stock',
    ];

    foreach ($keys as $key) {
        expect(localeLookup($id, $key))->not->toBe(localeLookup($en, $key), "{$key} not translated");
    }

    expect(localeLookup($id, 'status.partially_received'))->toBe('Diterima Sebagian')
        ->and(localeLookup($id, 'filter.movement_transfer_out'))->toBe('Transfer Keluar');
});
