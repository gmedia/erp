<?php

use App\Health\Checks\ObjectStorageCheck;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

uses()->group('system-health');

test('object storage check passes when write read and delete succeed', function () {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);

    $result = (new ObjectStorageCheck)->run();

    expect((string) $result->status)->toBe('ok');
    expect(Storage::disk('local')->allFiles('health-probes'))->toBe([]);
});

test('object storage check fails when the disk rejects writes', function () {
    config(['filesystems.default' => 'local']);

    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('put')->once()->andReturn(false);
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);

    $result = (new ObjectStorageCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())
        ->toContain('gagal menulis')
        ->toContain('bash scripts/minio-bucket.sh');
});

test('object storage check hides connection details when the disk throws', function () {
    config(['filesystems.default' => 'local']);

    Storage::shouldReceive('disk')->with('local')->andThrow(
        new RuntimeException('connect to https://minio.internal.example:9000 with key AKIAEXAMPLE failed')
    );

    $result = (new ObjectStorageCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())
        ->not->toContain('minio.internal.example')
        ->not->toContain('AKIAEXAMPLE')
        ->toContain('RuntimeException');
});
