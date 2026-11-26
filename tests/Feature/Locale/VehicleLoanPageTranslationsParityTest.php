<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeVehicleLoanPageTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return Arr::dot($decoded['vehicle_loan_page'] ?? []);
}

test('vehicle_loan_page translation keys exist in both english and indonesian locales', function () {
    $en = localeVehicleLoanPageTranslations('en.json');
    $id = localeVehicleLoanPageTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('vehicle_loan_page indonesian labels are translated, not copied from english', function () {
    $en = localeVehicleLoanPageTranslations('en.json');
    $id = localeVehicleLoanPageTranslations('id.json');

    // Pengecualian eksplisit: satuan yang sama di kedua bahasa (bukan teks yang belum diterjemahkan).
    $sameAcrossLocales = ['return.odometer_unit'];

    foreach (array_keys($en) as $key) {
        if (in_array($key, $sameAcrossLocales, true)) {
            continue;
        }

        expect($id[$key])->not->toBe($en[$key], "vehicle_loan_page.{$key} not translated");
    }
});

test('vehicle loan page sources read vehicle_loan_page keys without raw ui text', function () {
    $sources = [
        'pages/vehicle-loans/pickup/index.tsx',
        'pages/vehicle-loans/request/index.tsx',
        'pages/vehicle-loans/return/index.tsx',
        'pages/vehicle-loans/security/index.tsx',
        'components/vehicle-loans/VehicleLoanQrPanel.tsx',
    ];

    $en = localeVehicleLoanPageTranslations('en.json');
    $allSource = '';

    foreach ($sources as $source) {
        $content = File::get(resource_path('js/' . $source));
        $allSource .= $content;

        expect(preg_match('/<(p|span|label|dt|dd|h[1-6])[^>]*>\s*[A-Z][a-z]+ [a-z]+/', $content))
            ->toBe(0, "{$source} masih memakai teks UI mentah");
    }

    foreach (array_keys($en) as $key) {
        expect(str_contains($allSource, "'{$key}'"))->toBeTrue("vehicle_loan_page.{$key} tidak dipakai di sumber");
    }
});
