<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeApiErrorTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['api_error'] ?? [];
}

test('api_error translation keys exist in both english and indonesian locales', function () {
    $en = localeApiErrorTranslations('en.json');
    $id = localeApiErrorTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('api_error english messages keep the original english text', function () {
    $en = localeApiErrorTranslations('en.json');

    expect($en)->toBe([
        'network' => 'Network connection failed. Please check your internet connection.',
        'invalid_request' => 'Invalid request data.',
        'unauthorized' => 'Authentication required. Please log in again.',
        'forbidden' => 'You do not have permission to perform this action.',
        'not_found' => 'The requested resource was not found.',
        'validation_failed' => 'Validation failed.',
        'server' => 'Server error. Please try again later.',
        'unexpected' => 'An unexpected error occurred.',
    ]);
});

test('api_error messages are translated to indonesian', function () {
    $en = localeApiErrorTranslations('en.json');
    $id = localeApiErrorTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "api_error.{$key} not translated");
    }
});

test('error handling module uses api_error keys with english fallbacks', function () {
    $source = File::get(resource_path('js/utils/errorHandling.ts'));
    $en = localeApiErrorTranslations('en.json');

    foreach ($en as $key => $fallback) {
        expect(preg_match("/localizedError\(\s*'{$key}',\s*'" . preg_quote($fallback, '/') . "'/", $source))->toBe(1);
    }
});
