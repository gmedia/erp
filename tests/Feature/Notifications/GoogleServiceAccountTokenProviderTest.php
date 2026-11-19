<?php

use App\Contracts\PushSender;
use App\Services\Push\FcmPushSender;
use App\Services\Push\GoogleServiceAccountTokenProvider;
use App\Services\Push\LogPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class)->group('notifications');

test('google token provider rejects a missing credentials path', function () {
    Cache::flush();
    config(['services.fcm.credentials' => '']);

    expect(fn () => app(GoogleServiceAccountTokenProvider::class)->get())
        ->toThrow(RuntimeException::class, 'FCM credentials file is not configured.');
});

test('google token provider rejects a non-existent credentials file', function () {
    Cache::flush();
    config(['services.fcm.credentials' => '/tmp/missing-fcm-' . uniqid() . '.json']);

    expect(fn () => app(GoogleServiceAccountTokenProvider::class)->get())
        ->toThrow(RuntimeException::class, 'FCM credentials file is not configured.');
});

test('google token provider rejects invalid json', function () {
    Cache::flush();

    $path = tempnam(sys_get_temp_dir(), 'fcm');
    expect($path)->not->toBeFalse();

    $path = (string) $path;
    file_put_contents($path, '{invalid');
    config(['services.fcm.credentials' => $path]);

    try {
        expect(fn () => app(GoogleServiceAccountTokenProvider::class)->get())
            ->toThrow(LogicException::class);
    } finally {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});

test('google token provider rejects credentials without a private key', function () {
    Cache::flush();

    $path = tempnam(sys_get_temp_dir(), 'fcm');
    expect($path)->not->toBeFalse();

    $path = (string) $path;
    file_put_contents($path, json_encode(['client_email' => 'test@example.com']));
    config(['services.fcm.credentials' => $path]);

    try {
        expect(fn () => app(GoogleServiceAccountTokenProvider::class)->get())
            ->toThrow(InvalidArgumentException::class);
    } finally {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});

test('push sender binding follows the configured driver', function () {
    config(['notifications.push.driver' => 'fcm']);
    expect(app(PushSender::class))->toBeInstanceOf(FcmPushSender::class);

    config(['notifications.push.driver' => 'log']);
    expect(app(PushSender::class))->toBeInstanceOf(LogPushSender::class);
});
