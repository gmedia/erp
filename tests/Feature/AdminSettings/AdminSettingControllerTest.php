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
    test('unauthenticated user is redirected to login', function () {
        $response = $this->get(route('admin-settings.index'));
        $response->assertRedirect(route('login'));
    });

    test('user without permission gets 403', function () {
        $user = createTestUserWithPermissions([]);

        $response = $this->actingAs($user)->get(route('admin-settings.index'));
        $response->assertStatus(403);
    });

    test('user with admin_setting permission can view settings', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        $response = $this->actingAs($user)->get(route('admin-settings.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin-settings/index')
            ->has('settings')
            ->has('settings.general')
            ->has('settings.regional')
        );
    });

    test('index returns grouped settings with correct keys', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        $response = $this->actingAs($user)->get(route('admin-settings.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('settings.general.company_name')
            ->has('settings.general.company_address')
            ->has('settings.general.company_phone')
            ->has('settings.general.company_email')
            ->has('settings.general.company_logo_path')
            ->has('settings.general.company_logo_url')
            ->has('settings.regional.timezone')
            ->has('settings.regional.currency')
            ->has('settings.regional.date_format')
            ->has('settings.regional.number_format_decimal')
            ->has('settings.regional.number_format_thousand')
        );
    });
});

describe('AdminSettingController@update', function () {
    test('unauthenticated user is redirected to login', function () {
        $response = $this->put(route('admin-settings.update'), [
            'company_name' => 'Test',
        ]);
        $response->assertRedirect(route('login'));
    });

    test('user without edit permission gets 403', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'company_name' => 'Test Company',
        ]);

        $response->assertStatus(403);
    });

    test('user with edit permission can update general settings', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'company_name' => 'Acme Corporation',
            'company_address' => '123 Main Street',
            'company_phone' => '+628123456789',
            'company_email' => 'info@acme.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        expect(Setting::get('company_name'))->toBe('Acme Corporation');
        expect(Setting::get('company_address'))->toBe('123 Main Street');
        expect(Setting::get('company_phone'))->toBe('+628123456789');
        expect(Setting::get('company_email'))->toBe('info@acme.com');
    });

    test('user with edit permission can update regional settings', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'timezone' => 'Asia/Makassar',
            'currency' => 'USD',
            'date_format' => 'Y-m-d',
            'number_format_decimal' => '.',
            'number_format_thousand' => ',',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        expect(Setting::get('timezone'))->toBe('Asia/Makassar');
        expect(Setting::get('currency'))->toBe('USD');
        expect(Setting::get('date_format'))->toBe('Y-m-d');
        expect(Setting::get('number_format_decimal'))->toBe('.');
        expect(Setting::get('number_format_thousand'))->toBe(',');
    });

    test('update validates email format', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'company_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('company_email');
    });

    test('update validates timezone', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertSessionHasErrors('timezone');
    });

    test('update validates max length', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'company_name' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors('company_name');
    });

    test('partial update only changes submitted fields', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        // Set initial values
        Setting::set('company_name', 'Initial Company');
        Setting::set('company_phone', '111');

        // Update only company_name
        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'company_name' => 'Updated Company',
        ]);

        $response->assertRedirect();

        expect(Setting::get('company_name'))->toBe('Updated Company');
        // company_phone should remain unchanged
        expect(Setting::get('company_phone'))->toBe('111');
    });

    test('user can upload company logo svg', function () {
        Storage::fake(config('filesystems.default'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'company_logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $storedPath = Setting::get('company_logo_path');
        expect($storedPath)->toBeString();

        Storage::disk(config('filesystems.default'))->assertExists($storedPath);
    });
});

describe('AdminSettingController@testSmtp', function () {
    test('unauthenticated user is redirected to login', function () {
        $response = $this->post(route('admin-settings.test-smtp'), [
            'test_email' => 'test@example.com',
        ]);
        $response->assertRedirect(route('login'));
    });

    test('user without edit permission gets 403', function () {
        $user = createTestUserWithPermissions(['admin_setting']);

        $response = $this->actingAs($user)->post(route('admin-settings.test-smtp'), [
            'test_email' => 'test@example.com',
        ]);

        $response->assertStatus(403);
    });

    test('user with edit permission can send test email successfully', function () {
        \Illuminate\Support\Facades\Mail::fake();

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->post(route('admin-settings.test-smtp'), [
            'test_email' => 'test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TestSmtpMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    });

    test('fails gracefully when sending test email results in exception', function () {
        // Mock the mailer to throw an exception
        \Illuminate\Support\Facades\Mail::shouldReceive('to->send')->andThrow(new \Exception('Connection refused'));

        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->post(route('admin-settings.test-smtp'), [
            'test_email' => 'test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('test_email');
        expect(session('errors')->get('test_email')[0])->toContain('Failed to send email: Connection refused');
    });

    test('validates email format', function () {
        $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);

        $response = $this->actingAs($user)->post(route('admin-settings.test-smtp'), [
            'test_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('test_email');
    });
});
