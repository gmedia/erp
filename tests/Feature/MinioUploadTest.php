<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('upload file', function () {
    Storage::fake('s3');

    $disk = Storage::disk('s3');
    $file = UploadedFile::fake()->image('test.jpg');

    $path = 'tests/' . now()->timestamp . '.jpg';
    $success = $disk->put($path, $file->getContent());

    expect($success)->toBeTrue();

    // Assert the file exists in the fake disk
    $disk->assertExists($path);
})->group('minio');
