<?php

use App\Mail\TestSmtpMail;
use App\Models\Setting;
use Database\Seeders\AuthenticationSettingSeeder;
use Database\Seeders\SettingSampleDataSeeder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('admin-settings');

beforeEach(function () {
    $this->seed(SettingSampleDataSeeder::class);
    $this->seed(AuthenticationSettingSeeder::class);
});

describe('AdminSettingController@index', function () {
    test('unauthenticated user is returned 401', function () {
        $response = $this->getJson('/api/admin-settings');
        $response->assertStatus(401);
    });

    test('user without permission gets 403', function () {
        $user = createTestUserWithPermissions([]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/admin-settings');
        $response->assertStatus(403);
    });

    test('user with admin_setting permission can view settings', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/admin-settings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'settings' => [
                'general',
                'regional',
            ],
        ]);
    });

    test('index returns grouped settings with correct keys', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/admin-settings');

        $response->assertJsonStructure([
            'settings' => [
                'general' => [
                    'company_name',
                    'company_address',
                    'company_phone',
                    'company_email',
                    'company_logo_path',
                    'company_logo_url',
                ],
                'regional' => [
                    'currency',
                    'date_format',
                    'number_format_decimal',
                    'number_format_thousand',
                    'number_format_hide_decimal',
                ],
            ],
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

    test('user with edit permission can update security log settings', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $this->putJson('/api/admin-settings', [
            'security_log_retention_days' => 730,
            'security_log_failed_login_threshold' => 8,
            'security_log_failed_login_window_minutes' => 30,
        ])->assertOk();

        expect(Setting::get('security_log_retention_days'))->toBe(730);
        expect(Setting::get('security_log_failed_login_threshold'))->toBe(8);
        expect(Setting::get('security_log_failed_login_window_minutes'))->toBe(30);
    });

    test('security log settings outside allowed range are rejected', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $this->putJson('/api/admin-settings', [
            'security_log_retention_days' => 7,
            'security_log_failed_login_threshold' => 0,
            'security_log_failed_login_window_minutes' => 2000,
        ])->assertStatus(422)->assertJsonValidationErrors([
            'security_log_retention_days',
            'security_log_failed_login_threshold',
            'security_log_failed_login_window_minutes',
        ]);
    });

    test('user with edit permission can update general settings', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
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

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'currency' => 'IDR',
            'date_format' => 'Y-m-d',
            'number_format_decimal' => '.',
            'number_format_thousand' => ',',
            'number_format_hide_decimal' => true,
        ]);

        $response->assertOk();

        expect(Setting::get('currency'))->toBe('IDR');
        expect(Setting::get('date_format'))->toBe('Y-m-d');
        expect(Setting::get('number_format_decimal'))->toBe('.');
        expect(Setting::get('number_format_thousand'))->toBe(',');
        expect(Setting::get('number_format_hide_decimal'))->toBeTrue();
    });

    test('rejects unsupported currency aligned with config', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'currency' => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currency']);
    });

    test('update validates email format', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('company_email');
    });

    test('update validates max length', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
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
        Sanctum::actingAs($user, ['*']);
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

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson('/api/admin-settings', [
            'company_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertOk();

        $storedPath = Setting::get('company_logo_path');
        expect($storedPath)->toBeString();

        Storage::disk(config('filesystems.default'))->assertExists($storedPath);
    });

    test('user can upload company logo svg via multipart post request', function () {
        Storage::fake(config('filesystems.default'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->post('/api/admin-settings', [
            'company_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertOk();

        $storedPath = Setting::get('company_logo_path');
        expect($storedPath)->toBeString();

        Storage::disk(config('filesystems.default'))->assertExists($storedPath);
    });

    test('user can upload company logo svg content via json payload', function () {
        Storage::fake(config('filesystems.default'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings', [
            'company_logo_svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="#f97316"/></svg>',
        ]);

        $response->assertOk();

        $storedPath = Setting::get('company_logo_path');
        expect($storedPath)->toBeString();
        expect($storedPath)->toEndWith('.svg');

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
        Mail::fake();

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'test@example.com',
        ]);

        $response->assertOk();

        Mail::assertSent(TestSmtpMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    });

    test('fails gracefully when sending test email results in exception', function () {
        // Mock the mailer to throw an exception
        Mail::shouldReceive('to->send')->andThrow(new Exception('Connection refused'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('test_email');
        $response->assertJsonPath('errors.test_email.0', 'Failed to send email: Connection refused');
    });

    test('validates email format', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/admin-settings/test-smtp', [
            'test_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('test_email');
    });
});

describe('AdminSettingController@update logo write failures', function () {
    beforeEach(function () {
        Setting::set('company_name', 'Initial Company');
        Setting::set('company_logo_path', 'branding/logos/previous.svg');
    });

    test('failed logo write returns localized 422 and saves no settings', function (
        string $upload,
        string $method,
        bool $throws,
    ) {
        mockLogoDiskWriteFailure($method, $throws ? new RuntimeException('Connection refused') : null);

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);
        Sanctum::actingAs($user, ['*']);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
            . '<rect width="10" height="10" fill="#f97316"/></svg>';
        $logoPayload = $upload === 'file'
            ? ['company_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]
            : ['company_logo_svg' => $svg];

        $response = $this->post('/api/admin-settings', array_merge(
            ['company_name' => 'Changed Company'],
            $logoPayload,
        ), ['Accept' => 'application/json']);

        $response->assertStatus(422)
            ->assertJsonPath('message', __('admin_settings.logo_upload_failed'))
            ->assertJsonPath('errors.company_logo.0', __('admin_settings.logo_upload_failed'));

        expect(Setting::get('company_name'))->toBe('Initial Company');
        expect(Setting::get('company_logo_path'))->toBe('branding/logos/previous.svg');
    })->with([
        'file write returns false' => ['file', 'putFileAs', false],
        'file write throws' => ['file', 'putFileAs', true],
        'svg write returns false' => ['svg', 'put', false],
        'svg write throws' => ['svg', 'put', true],
    ]);

    test('logo failure message exists in both en and id translations', function () {
        foreach (['en', 'id'] as $locale) {
            $translations = json_decode((string) file_get_contents(lang_path("{$locale}.json")), true);

            expect($translations)->toHaveKey('admin_settings.logo_upload_failed');
        }
    });
});

/**
 * Make the default disk fail one write method, either by returning false or by throwing.
 */
function mockLogoDiskWriteFailure(string $method, ?Throwable $throws = null): void
{
    $disk = Mockery::mock(FilesystemAdapter::class);
    $expectation = $disk->shouldReceive($method);

    if ($throws instanceof Throwable) {
        $expectation->andThrow($throws);
    } else {
        $expectation->andReturn(false);
    }

    Storage::shouldReceive('disk')->andReturn($disk);
}
