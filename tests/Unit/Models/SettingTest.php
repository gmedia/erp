<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('admin-settings');

describe('Setting Model', function () {
    beforeEach(function () {
        // Seed default settings
        $this->seed(\Database\Seeders\SettingSeeder::class);
    });

    test('get returns default value when key does not exist', function () {
        $result = Setting::get('non_existent_key', 'default_value');
        expect($result)->toBe('default_value');
    });

    test('get returns null when key does not exist and no default', function () {
        $result = Setting::get('non_existent_key');
        expect($result)->toBeNull();
    });

    test('get returns value for existing key', function () {
        $result = Setting::get('timezone');
        expect($result)->toBe('Asia/Jakarta');
    });

    test('set updates existing setting value', function () {
        Setting::set('company_name', 'Test Company');
        expect(Setting::get('company_name'))->toBe('Test Company');
    });

    test('set throws exception for non-existent key', function () {
        expect(fn () => Setting::set('non_existent_key', 'value'))
            ->toThrow(\InvalidArgumentException::class);
    });

    test('getGrouped returns settings grouped by group', function () {
        Setting::set('company_name', 'Test Company');

        $grouped = Setting::getGrouped();

        expect($grouped)->toBeArray()
            ->toHaveKeys(['general', 'regional']);
        expect($grouped['general'])->toHaveKey('company_name');
        expect($grouped['general']['company_name'])->toBe('Test Company');
        expect($grouped['regional'])->toHaveKey('timezone');
        expect($grouped['regional']['timezone'])->toBe('Asia/Jakarta');
    });

    test('getByGroup returns settings for a specific group', function () {
        $general = Setting::getByGroup('general');

        expect($general)->toBeArray()
            ->toHaveKeys(['company_name', 'company_address', 'company_phone', 'company_email']);
    });

    test('castValue handles integer type', function () {
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_integer',
            'value' => '42',
            'type' => 'integer',
        ]);

        expect(Setting::get('test_integer'))->toBe(42);
    });

    test('castValue handles boolean type', function () {
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_boolean_true',
            'value' => '1',
            'type' => 'boolean',
        ]);
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_boolean_false',
            'value' => '0',
            'type' => 'boolean',
        ]);

        expect(Setting::get('test_boolean_true'))->toBeTrue();
        expect(Setting::get('test_boolean_false'))->toBeFalse();
    });

    test('castValue handles json type', function () {
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_json',
            'value' => '{"foo":"bar"}',
            'type' => 'json',
        ]);

        $result = Setting::get('test_json');
        expect($result)->toBeArray()
            ->toHaveKey('foo', 'bar');
    });

    test('castValue handles null value', function () {
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_null',
            'value' => null,
            'type' => 'string',
        ]);

        expect(Setting::get('test_null'))->toBeNull();
    });

    test('set serializes boolean values', function () {
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_bool_set',
            'value' => '0',
            'type' => 'boolean',
        ]);

        Setting::set('test_bool_set', true);
        expect(Setting::where('key', 'test_bool_set')->value('value'))->toBe('1');

        Setting::set('test_bool_set', false);
        expect(Setting::where('key', 'test_bool_set')->value('value'))->toBe('0');
    });

    test('set serializes json values', function () {
        Setting::factory()->create([
            'group' => 'test',
            'key' => 'test_json_set',
            'value' => '{}',
            'type' => 'json',
        ]);

        Setting::set('test_json_set', ['hello' => 'world']);
        expect(Setting::where('key', 'test_json_set')->value('value'))->toBe('{"hello":"world"}');
    });

    test('model has correct fillable attributes', function () {
        $setting = new Setting();
        expect($setting->getFillable())->toBe(['group', 'key', 'value', 'type']);
    });
});
