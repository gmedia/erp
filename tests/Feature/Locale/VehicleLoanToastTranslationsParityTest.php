<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeVehicleLoanToastTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['vehicle_loan_toast'] ?? [];
}

test('vehicle_loan_toast translation keys exist in both english and indonesian locales', function () {
    $en = localeVehicleLoanToastTranslations('en.json');
    $id = localeVehicleLoanToastTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('vehicle_loan_toast indonesian messages are translated, not copied from english', function () {
    $en = localeVehicleLoanToastTranslations('en.json');
    $id = localeVehicleLoanToastTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "vehicle_loan_toast.{$key} not translated");
    }
});

test('vehicle loan toast sources read vehicle_loan_toast keys without english literals', function () {
    $sources = [
        'pages/vehicle-loans/pickup/index.tsx',
        'pages/vehicle-loans/request/index.tsx',
        'pages/vehicle-loans/return/index.tsx',
        'pages/vehicle-loans/security/index.tsx',
    ];

    $en = localeVehicleLoanToastTranslations('en.json');
    $allSource = '';

    foreach ($sources as $source) {
        $content = File::get(resource_path('js/' . $source));
        $allSource .= $content;

        expect(preg_match("/toast\.(success|error)\(\s*['`\"]/", $content))
            ->toBe(0, "{$source} masih memakai toast literal");

        expect($content)->toContain("useToastTranslator('vehicle_loan_toast')");
    }

    foreach (array_keys($en) as $key) {
        expect(str_contains($allSource, "'{$key}'"))->toBeTrue();
    }
});
