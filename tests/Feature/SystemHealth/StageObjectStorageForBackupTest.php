<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses()->group('system-health');

function stagingTestRoot(): string
{
    return storage_path('framework/testing/backup-staging');
}

function objectsTestRoot(): string
{
    return storage_path('framework/testing/objects-src');
}

beforeEach(function () {
    File::deleteDirectory(stagingTestRoot());
    File::deleteDirectory(objectsTestRoot());

    config([
        'backup.staging.path' => stagingTestRoot(),
        'backup.staging.max_files' => 100,
        'backup.staging.max_bytes' => 1024 * 1024,
        'filesystems.default' => 'objects',
        'filesystems.disks.objects' => [
            'driver' => 'local',
            'root' => objectsTestRoot(),
            'throw' => false,
        ],
    ]);

    Storage::disk('objects')->put('branch/a.txt', 'alpha');
    Storage::disk('objects')->put('branch/nested/b.txt', 'beta');
});

afterEach(function () {
    File::deleteDirectory(stagingTestRoot());
    File::deleteDirectory(objectsTestRoot());
});

test('stage command copies every object into the staging folder', function () {
    $this->artisan('backup:stage-object-storage')->assertSuccessful();

    expect(file_get_contents(stagingTestRoot() . '/branch/a.txt'))->toBe('alpha')
        ->and(file_get_contents(stagingTestRoot() . '/branch/nested/b.txt'))->toBe('beta');
});

test('stage command skips objects that have not changed since the last run', function () {
    $this->artisan('backup:stage-object-storage')->assertSuccessful();

    $this->artisan('backup:stage-object-storage')
        ->expectsOutputToContain('0 disalin, 2 tidak berubah')
        ->assertSuccessful();
});

test('stage command stops at the configured file limit', function () {
    config(['backup.staging.max_files' => 1]);

    $this->artisan('backup:stage-object-storage')
        ->expectsOutputToContain('Batas staging tercapai')
        ->assertSuccessful();

    expect(File::allFiles(stagingTestRoot()))->toHaveCount(1);
});

test('clear option removes the staging folder', function () {
    $this->artisan('backup:stage-object-storage')->assertSuccessful();
    expect(is_dir(stagingTestRoot()))->toBeTrue();

    $this->artisan('backup:stage-object-storage', ['--clear' => true])->assertSuccessful();

    expect(is_dir(stagingTestRoot()))->toBeFalse();
});
