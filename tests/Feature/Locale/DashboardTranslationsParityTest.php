<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeDashboardTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['dashboard'] ?? [];
}

test('dashboard translation keys exist in both english and indonesian locales', function () {
    $en = localeDashboardTranslations('en.json');
    $id = localeDashboardTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('dashboard english labels keep the original english text', function () {
    $en = localeDashboardTranslations('en.json');

    expect($en['total_customers'])->toBe('Total Customers')
        ->and($en['total_employees'])->toBe('Total Employees')
        ->and($en['total_suppliers'])->toBe('Total Suppliers')
        ->and($en['total_assets'])->toBe('Total Assets')
        ->and($en['shortcuts'])->toBe('Shortcuts')
        ->and($en['shortcut_my_approvals_description'])->toBe('Review pending requests')
        ->and($en['shortcut_purchase_orders_description'])->toBe('Open the PO list')
        ->and($en['shortcut_stock_monitor_description'])->toBe('On-hand quantity by warehouse')
        ->and($en['master_data_mix'])->toBe('Master data mix')
        ->and($en['customers'])->toBe('Customers')
        ->and($en['employees'])->toBe('Employees')
        ->and($en['suppliers'])->toBe('Suppliers')
        ->and($en['assets'])->toBe('Assets')
        ->and($en['mix_footer'])->toBe(
            'Operational counts only. Financial KPIs stay on Financial Dashboard.',
        );
});

test('dashboard labels are translated to indonesian', function () {
    $en = localeDashboardTranslations('en.json');
    $id = localeDashboardTranslations('id.json');

    foreach (array_keys($en) as $field) {
        expect($id[$field])->not->toBe($en[$field], "dashboard.{$field} not translated");
    }

    expect($id['shortcuts'])->toBe('Pintasan')
        ->and($id['master_data_mix'])->toBe('Komposisi data master');
});

test('dashboard page uses the translation keys with english fallbacks', function () {
    $source = File::get(resource_path('js/pages/dashboard.tsx'));

    $keys = [
        'total_customers',
        'total_employees',
        'total_suppliers',
        'total_assets',
        'shortcuts',
        'master_data_mix',
        'mix_footer',
    ];

    foreach ($keys as $key) {
        expect($source)->toContain("'dashboard.{$key}'");
    }

    expect($source)->toContain("'menu.dashboard', 'Dashboard'");
});
