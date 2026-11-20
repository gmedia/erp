<?php

use App\Contracts\FcmAccessTokenProvider;
use App\Models\DeviceToken;
use App\Services\Push\FcmPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class)->group('notifications');

function fakeFcmAccessTokenProvider(): void
{
    app()->instance(FcmAccessTokenProvider::class, new class implements FcmAccessTokenProvider
    {
        public function get(): string
        {
            return 'test-token';
        }
    });
}

test('fcm sender posts to the v1 endpoint with the access token', function () {
    Cache::flush();
    config(['services.fcm.project_id' => 'test-project']);
    fakeFcmAccessTokenProvider();

    $token = DeviceToken::factory()->create(['token' => 'fcm-token']);

    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([], 200),
    ]);

    app(FcmPushSender::class)->send($token, [
        'event_key' => 'approval.request.created',
        'title' => 'Approval',
        'body' => 'Please review',
        'target_url' => '/approvals/1',
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://fcm.googleapis.com/v1/projects/test-project/messages:send'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['message']['token'] === 'fcm-token'
            && $request['message']['data']['event_key'] === 'approval.request.created'
            && $request['message']['data']['target_url'] === '/approvals/1'
            && $request['message']['android']['notification']['channel_id'] === 'default';
    });
});

test('fcm sender deletes unregistered device tokens', function () {
    Cache::flush();
    config(['services.fcm.project_id' => 'test-project']);
    fakeFcmAccessTokenProvider();

    $token = DeviceToken::factory()->create(['token' => 'stale-token']);

    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'error' => [
                'status' => 'NOT_FOUND',
                'details' => [['errorCode' => 'UNREGISTERED']],
            ],
        ], 404),
    ]);

    app(FcmPushSender::class)->send($token, [
        'event_key' => 'test',
        'title' => 'Test',
        'body' => 'Test',
        'target_url' => '/',
    ]);

    expect(DeviceToken::find($token->id))->toBeNull();
});

test('fcm sender keeps tokens on server errors', function () {
    Cache::flush();
    config(['services.fcm.project_id' => 'test-project']);
    fakeFcmAccessTokenProvider();

    $token = DeviceToken::factory()->create();

    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'error' => ['status' => 'UNAVAILABLE'],
        ], 503),
    ]);

    app(FcmPushSender::class)->send($token, [
        'event_key' => 'test',
        'title' => 'Test',
        'body' => 'Test',
        'target_url' => '/',
    ]);

    expect(DeviceToken::find($token->id))->not->toBeNull();
});

test('fcm sender requires a project id', function () {
    Cache::flush();
    config(['services.fcm.project_id' => '']);
    fakeFcmAccessTokenProvider();

    $token = DeviceToken::factory()->create();

    expect(fn () => app(FcmPushSender::class)->send($token, []))
        ->toThrow(RuntimeException::class, 'FCM project id is not configured.');
});
