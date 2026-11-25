<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('locale');

test('public translations endpoint returns the requested locale without authentication', function () {
    getJson('/api/locale/translations', ['X-Locale' => 'id'])
        ->assertOk()
        ->assertJsonPath('locale', 'id')
        ->assertJsonPath('translations.auth.login', 'Masuk');
});

test('public translations endpoint falls back to english for unknown locale', function () {
    getJson('/api/locale/translations', ['X-Locale' => 'fr'])
        ->assertOk()
        ->assertJsonPath('locale', config('app.locale'))
        ->assertJsonPath('translations.auth.login', 'Log in');
});
