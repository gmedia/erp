<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function issue595LocaleJson(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

function issue595LocaleLookup(array $locale, string $dottedKey): ?string
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

test('issue 595 filter modal keeps the english text of main', function () {
    $en = issue595LocaleJson('en.json');

    expect(issue595LocaleLookup($en, 'common.filter_modal.title'))->toBe('Filters')
        ->and(issue595LocaleLookup($en, 'common.filter_modal.description'))->toBe('Apply filters to refine the results')
        ->and(issue595LocaleLookup($en, 'common.filter_modal.reset'))->toBe('Reset')
        ->and(issue595LocaleLookup($en, 'common.filter_modal.clear_all'))->toBe('Clear All')
        ->and(issue595LocaleLookup($en, 'common.filter_modal.apply'))->toBe('Apply Filters');
});

test('issue 595 filter modal is translated to indonesian', function () {
    $id = issue595LocaleJson('id.json');

    expect(issue595LocaleLookup($id, 'common.filter_modal.title'))->toBe('Filter')
        ->and(issue595LocaleLookup($id, 'common.filter_modal.description'))->toBe('Terapkan filter untuk menyaring hasil')
        ->and(issue595LocaleLookup($id, 'common.filter_modal.reset'))->toBe('Atur ulang')
        ->and(issue595LocaleLookup($id, 'common.filter_modal.clear_all'))->toBe('Hapus semua')
        ->and(issue595LocaleLookup($id, 'common.filter_modal.apply'))->toBe('Terapkan filter');
});

test('issue 595 security log filter placeholders are localized in indonesian', function () {
    $en = issue595LocaleJson('en.json');
    $id = issue595LocaleJson('id.json');

    expect(issue595LocaleLookup($en, 'page.security_logs.select_event'))->toBe('Select Event')
        ->and(issue595LocaleLookup($id, 'page.security_logs.select_event'))->toBe('Pilih kejadian')
        ->and(issue595LocaleLookup($en, 'page.security_logs.ip_placeholder'))->toBe('e.g. 192.168.1.10')
        ->and(issue595LocaleLookup($id, 'page.security_logs.ip_placeholder'))->toBe('mis. 192.168.1.10');
});

test('issue 595 system health summary values are message codes in both locales', function () {
    $en = issue595LocaleJson('en.json');
    $id = issue595LocaleJson('id.json');

    foreach (['passed', 'complete', 'unavailable'] as $code) {
        expect(issue595LocaleLookup($en, "system_health.summary.{$code}"))->not->toBeNull()
            ->and(issue595LocaleLookup($id, "system_health.summary.{$code}"))->not->toBeNull();
    }

    expect(issue595LocaleLookup($id, 'system_health.summary.passed'))->toBe('Lulus')
        ->and(issue595LocaleLookup($id, 'system_health.summary.complete'))->toBe('Lengkap')
        ->and(issue595LocaleLookup($id, 'system_health.summary.unavailable'))->toBe('Tidak tersedia');
});

test('issue 595 security log date filters render a single label', function () {
    $source = File::get(resource_path('js/components/security-logs/Filters.tsx'));

    expect(substr_count($source, 'label: t(\'page.security_logs.start_date\')'))->toBe(1)
        ->and($source)->not->toContain("label={t('page.security_logs.start_date')}")
        ->and($source)->not->toContain("label={t('page.security_logs.end_date')}");
});
