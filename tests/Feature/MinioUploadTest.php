<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

test('it can upload a file to minio s3 disk', function () {
    // We want to test the actual S3 disk, not a fake, to ensure credentials work
    $disk = Storage::disk('s3');
    
    // Create a fake uploaded file
    $file = UploadedFile::fake()->image('test-avatar.jpg');
    
    // Generate a unique path
    $path = 'testing/' . uniqid() . '-' . $file->getClientOriginalName();
    
    // Attempt to put the file on the S3 disk
    $success = $disk->put($path, $file->getContent());
    
    // Assert the upload was successful
    expect($success)->toBeTrue();
    
    // Assert the file actually exists on the disk
    expect($disk->exists($path))->toBeTrue();
    
    // Clean up
    $disk->delete($path);
    expect($disk->exists($path))->toBeFalse();
})->group('integration', 's3', 'minio');

