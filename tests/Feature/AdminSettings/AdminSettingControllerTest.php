<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class)->group('admin-settings');

beforeEach(function () {
    $this->seed(\Database\Seeders\SettingSampleDataSeeder::class);
});

describe('AdminSettingController@index', function () {
    test('unauthenticated user is returned 401', function () {
        $response = $this->getJson('/api/admin-settings');
        $response->assertStatus(401);
    });

    test('user without permission gets 403', function () {
        $user = createTestUserWithPermissions([]);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/admin-settings');
        $response->assertStatus(403);
    });

    test('user with admin_setting permission can view settings', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/admin-settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'settings' => [
                'general',
                'regional'
            ]
        ]);
    });

    test('index returns grouped settings with correct keys', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/admin-settings');

        $response->assertJsonStructure([
            'settings' => [
                'general' => [
                    'company_name',
                    'company_address',
                    'company_phone',
                    'company_email',
                    'company_logo_path',
                    'company_logo_url'
                ],
                'regional' => [
                    'timezone',
                    'currency',
                    'date_format',
                    'number_format_decimal',
                    'number_format_thousand'
                ]
            ]
        ]);
    });
});

describe('AdminSettingController@update', function () {
    test('unauthenticated user is returned 401', function () {
        $response = $this->putJson('/api/admin-settings', [
            'company_name' => 'Test',
        ]);
        $response->assertStatus(401);
    });



    test('user with edit permission can update general settings', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_name' => 'Acme Corporation',
            'company_address' => '123 Main Street',
            'company_phone' => '+628123456789',
            'company_email' => 'info@acme.com',
        ]);

        $response->assertOk();

        expect(Setting::get('company_name'))->toBe('Acme Corporation');
        expect(Setting::get('company_address'))->toBe('123 Main Street');
        expect(Setting::get('company_phone'))->toBe('+628123456789');
        expect(Setting::get('company_email'))->toBe('info@acme.com');
    });

    test('user with edit permission can update regional settings', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'timezone' => 'Asia/Makassar',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'number_format_decimal' => '.',
            'number_format_thousand' => ',',
        ]);

        $response->assertOk();

        expect(Setting::get('timezone'))->toBe('Asia/Makassar');
        expect(Setting::get('currency'))->toBe('USD');
        expect(Setting::get('date_format'))->toBe('Y-m-d');
        expect(Setting::get('number_format_decimal'))->toBe('.');
        expect(Setting::get('number_format_thousand'))->toBe(',');
    });

    test('update validates email format', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('company_email');
    });

    test('update validates timezone', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('timezone');
    });

    test('update validates max length', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('company_name');
    });

    test('partial update only changes submitted fields', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        // Set initial values
        Setting::set('company_name', 'Initial Company');
        Setting::set('company_phone', '111');

        // Update only company_name
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_name' => 'Updated Company',
        ]);

        $response->assertOk();

        expect(Setting::get('company_name'))->toBe('Updated Company');
        // company_phone should remain unchanged
        expect(Setting::get('company_phone'))->toBe('111');
    });

    test('user can upload company logo svg', function () {
        Storage::fake(config('filesystems.default'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertOk();

        $storedPath = Setting::get('company_logo_path');
        expect($storedPath)->toBeString();

        Storage::disk(config('filesystems.default'))->assertExists($storedPath);
    });
});

describe('AdminSettingController@testSmtp', function () {
    test('unauthenticated user is returned 401', function () {
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'test@example.com',
        ]);
        $response->assertStatus(401);
    });



    test('user with edit permission can send test email successfully', function () {
        \Illuminate\Support\Facades\Mail::fake();

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'test@example.com',
        ]);

        $response->assertOk();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TestSmtpMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    });

    test('fails gracefully when sending test email results in exception', function () {
        // Mock the mailer to throw an exception
        \Illuminate\Support\Facades\Mail::shouldReceive('to->send')->andThrow(new \Exception('Connection refused'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('test_email');
        $response->assertJsonPath('errors.test_email.0', 'Failed to send email: Connection refused');
    });

    test('validates email format', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('test_email');
    });
});
