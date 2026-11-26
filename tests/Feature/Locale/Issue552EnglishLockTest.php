<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function issue552LocaleJson(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

function issue552LocaleLookup(array $locale, string $dottedKey): ?string
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

test('issue 552 leftover labels keep the english text of main', function () {
    $en = issue552LocaleJson('en.json');

    expect(issue552LocaleLookup($en, 'common.actions'))->toBe('Actions')
        ->and(issue552LocaleLookup($en, 'nav.platform'))->toBe('Platform')
        ->and(issue552LocaleLookup($en, 'status.partially_ordered'))->toBe('Partially Ordered')
        ->and(issue552LocaleLookup($en, 'status.stocktake_result'))->toBe('Stocktake Result')
        ->and(issue552LocaleLookup($en, 'pipelineDashboard.staleThreshold'))->toBe('Stale Threshold')
        ->and(issue552LocaleLookup($en, 'pipelineDashboard.staleEntities'))->toBe('Stale Entities')
        ->and(issue552LocaleLookup($en, 'pipelineDashboard.selectPipeline'))->toBe('Select Pipeline')
        ->and(issue552LocaleLookup($en, 'filter.all_types'))->toBe('All types')
        ->and(issue552LocaleLookup($en, 'filter.all_statuses'))->toBe('All statuses');
});

test('issue 552 locale id provides translations for the new filter keys', function () {
    $id = issue552LocaleJson('id.json');

    expect(issue552LocaleLookup($id, 'filter.all_types'))->toBe('Semua Jenis')
        ->and(issue552LocaleLookup($id, 'filter.all_statuses'))->toBe('Semua Status');
});

test('issue 552 touched components keep their english fallback strings', function () {
    $actionsFallback = "/key:\\s*'common\\.actions',\\s*fallback:\\s*'Actions'/";

    foreach (['ActionsDropdown.tsx', 'TransactionLineItemsTable.tsx'] as $file) {
        $path = "resources/js/components/common/{$file}";
        $source = File::get(base_path($path));

        expect(preg_match($actionsFallback, $source))->toBe(1, "{$path} lost english fallback 'Actions'");
    }

    $filters = File::get(base_path('resources/js/components/common/filters.tsx'));

    expect($filters)->toContain("['partially_ordered', 'Partially Ordered']")
        ->and($filters)->toContain("['partially_received', 'Partially Received']");
});
