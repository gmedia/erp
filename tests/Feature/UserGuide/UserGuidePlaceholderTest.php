<?php

use Illuminate\Support\Facades\File;

uses()->group('user-guide');

test('user guides do not contain screenshot placeholders', function () {
    $guides = File::glob(base_path('docs/user-guide-*.md'));

    expect($guides)->not->toBeEmpty();

    $offenders = collect($guides)
        ->filter(fn (string $path) => str_contains(File::get($path), '[Screenshot'))
        ->map(fn (string $path) => basename($path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
