<?php

use App\Health\SystemHealthSanitizer;

uses()->group('system-health');

test('sanitizer removes urls ip addresses and inline credentials from messages', function () {
    $raw = 'Gagal ke https://minio.internal.example:9000/bucket, host 10.0.0.12:3306, password=rahasia123';

    $clean = SystemHealthSanitizer::message($raw);

    expect($clean)
        ->not->toContain('minio.internal.example')
        ->not->toContain('10.0.0.12')
        ->not->toContain('rahasia123')
        ->toContain('[url disembunyikan]')
        ->toContain('[alamat disembunyikan]')
        ->toContain('[kredensial disembunyikan]');
});

test('sanitizer returns empty string for null or empty message', function () {
    expect(SystemHealthSanitizer::message(null))->toBe('')
        ->and(SystemHealthSanitizer::message(''))->toBe('');
});

test('sanitizer drops sensitive metadata keys and keeps safe values', function () {
    $clean = SystemHealthSanitizer::meta([
        'disk_used_percent' => 41,
        'database_host' => '10.0.0.5',
        'db_password' => 'rahasia',
        'api_token' => 'abc',
        'scope' => 'container',
        'cpu_load_scope' => 'kernel',
    ]);

    expect($clean)
        ->toHaveKey('disk_used_percent', 41)
        ->toHaveKey('scope', 'container')
        ->toHaveKey('cpu_load_scope', 'kernel')
        ->not->toHaveKey('database_host')
        ->not->toHaveKey('db_password')
        ->not->toHaveKey('api_token');
});
