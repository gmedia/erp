<?php

use App\Support\ExportStorage;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    config()->set('filesystems.export_disk', 'public');
});

test('disk name follows the export_disk config', function () {
    expect(ExportStorage::diskName())->toBe('public');

    config()->set('filesystems.export_disk', 's3');

    expect(ExportStorage::diskName())->toBe('s3');
});

test('store writes the export to the configured disk', function () {
    Storage::fake('public');
    Excel::fake();

    ExportStorage::store(new stdClass, 'exports/probe.xlsx');

    Excel::assertStored('exports/probe.xlsx', 'public');
});

test('url returns a plain disk url for a non-object-storage disk', function () {
    Storage::fake('public');
    config()->set('filesystems.disks.public.driver', 'local');

    expect(ExportStorage::url('exports/probe.xlsx'))
        ->toContain('exports/probe.xlsx');
});

test('url is signed when the export disk uses the s3 driver', function () {
    config()->set('filesystems.export_disk', 'exports-s3');
    config()->set('filesystems.disks.exports-s3', [
        'driver' => 's3',
        'key' => 'k',
        'secret' => 's',
        'region' => 'us-east-1',
        'bucket' => 'b',
    ]);

    $url = ExportStorage::url('exports/probe.xlsx');

    expect($url)->toContain('X-Amz-Signature');
});
