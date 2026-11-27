<?php

use App\Health\SystemHealthMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

uses()->group('system-health');

/**
 * Kunci queue/schedule dalam bentuk titik (mis. system_health.queue.jobs_failed), dibaca dari
 * blok bertingkat `system_health` karena frontend `t()` menelusuri objek per titik.
 *
 * @return array<string, string>
 */
function systemHealthTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return collect(Arr::dot($decoded['system_health'] ?? []))
        ->filter(fn ($value, $key) => str_starts_with((string) $key, 'queue.')
            || str_starts_with((string) $key, 'schedule.'))
        ->mapWithKeys(fn ($value, $key) => ['system_health.' . $key => $value])
        ->all();
}

test('package message codes are registered in both english and indonesian locales', function () {
    $en = systemHealthTranslations('en.json');
    $id = systemHealthTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('package message codes are translated to indonesian', function () {
    $en = systemHealthTranslations('en.json');
    $id = systemHealthTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "{$key} not translated");
    }
});

test('queue failure message maps to a stable code with the failed queue count', function () {
    $resolved = SystemHealthMessage::fromPackage('Queue jobs running failed. Check meta for more information.', [
        'The `default` queue did not run yet.',
        'The last run of the `high` queue was more than 9 minutes ago.',
    ]);

    expect($resolved)->toBe([
        'code' => 'system_health.queue.jobs_failed',
        'params' => ['count' => '2'],
    ]);
});

test('schedule that never ran maps to a stable code without english text', function () {
    $resolved = SystemHealthMessage::fromPackage('The schedule did not run yet.');

    expect($resolved)->toBe(['code' => 'system_health.schedule.not_run_yet', 'params' => []]);
});

test('schedule stale message maps to a stable code', function () {
    $resolved = SystemHealthMessage::fromPackage('The last run of the schedule was more than 12 minutes ago.');

    expect($resolved['code'])->toBe('system_health.schedule.stale');
});

test('empty or unknown package messages do not produce a code', function () {
    expect(SystemHealthMessage::fromPackage(''))->toBe(['code' => null, 'params' => []]);
    expect(SystemHealthMessage::fromPackage('Some unknown text'))->toBe(['code' => null, 'params' => []]);
});

test('message codes resolve as nested keys the way the frontend t() reads them', function () {
    foreach (['en.json', 'id.json'] as $file) {
        $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

        $codes = [
            'system_health.queue.jobs_failed',
            'system_health.schedule.not_run_yet',
            'system_health.schedule.stale',
        ];

        foreach ($codes as $code) {
            $path = 'system_health.' . substr($code, strlen('system_health.'));

            expect(Arr::get($decoded, $path))->toBeString("{$file} missing nested key {$path}");
        }
    }
});

test('every message code emitted by the mapper exists in both locales', function () {
    $en = systemHealthTranslations('en.json');
    $id = systemHealthTranslations('id.json');

    $codes = [
        SystemHealthMessage::fromPackage('Queue jobs running failed.', ['x'])['code'],
        SystemHealthMessage::fromPackage('The schedule did not run yet.')['code'],
        SystemHealthMessage::fromPackage('The last run of the schedule was more than 3 minutes ago.')['code'],
    ];

    foreach ($codes as $code) {
        expect($en)->toHaveKey($code);
        expect($id)->toHaveKey($code);
    }
});
