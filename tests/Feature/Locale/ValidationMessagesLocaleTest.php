<?php

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('locale');

/**
 * @return array<string, mixed>
 */
function localeValidationLines(string $locale): array
{
    return require lang_path("{$locale}/validation.php");
}

beforeEach(function () {
    $user = createTestUserWithPermissions(['user', 'user.edit']);
    Sanctum::actingAs($user, ['*']);
});

test('validation 422 without X-Locale keeps the original english messages', function () {
    $employee = Employee::factory()->create();

    $response = postJson("/api/employees/{$employee->id}/user", [
        'name' => '',
        'email' => '',
    ]);

    $response->assertUnprocessable();
    expect($response->json('errors.name.0'))->toBe('The name field is required.');
    expect($response->json('errors.email.0'))->toBe('The email field is required.');
});

test('custom form request message stays english without X-Locale', function () {
    $employee = Employee::factory()->create();

    $response = postJson("/api/employees/{$employee->id}/permissions", [
        'permissions' => 'not-an-array',
    ]);

    $response->assertUnprocessable();
    expect($response->json('errors.permissions.0'))->toBe('The permissions must be an array.');
});

test('validation 422 with X-Locale id returns indonesian messages', function () {
    $employee = Employee::factory()->create();

    $response = postJson("/api/employees/{$employee->id}/user", [
        'name' => '',
        'email' => '',
    ], ['X-Locale' => 'id']);

    $response->assertUnprocessable();
    expect($response->json('errors.name.0'))->toBe(__('validation.form_messages.user_name_required', [], 'id'));
    expect($response->json('errors.email.0'))->toBe(__('validation.form_messages.user_email_required', [], 'id'));
});

test('custom form request message is indonesian with X-Locale id', function () {
    $employee = Employee::factory()->create();

    $response = postJson("/api/employees/{$employee->id}/permissions", [
        'permissions' => 'not-an-array',
    ], ['X-Locale' => 'id']);

    $response->assertUnprocessable();
    expect($response->json('errors.permissions.0'))->toBe('Izin harus berupa larik.');
});

test('generic rule message is translated to indonesian with X-Locale id', function () {
    $employee = Employee::factory()->create();

    $response = postJson("/api/employees/{$employee->id}/user", [
        'name' => 'Budi',
        'email' => 'bukan-email',
    ], ['X-Locale' => 'id']);

    $response->assertUnprocessable();
    expect($response->json('errors.email.0'))->toBe(trans('validation.email', ['attribute' => 'surel'], 'id'));
});

test('validation lines english keep framework default wording', function () {
    $en = localeValidationLines('en');

    expect($en['required'])->toBe('The :attribute field is required.');
    expect($en['email'])->toBe('The :attribute field must be a valid email address.');
    expect($en['min']['string'])->toBe('The :attribute field must be at least :min characters.');
});

test('indonesian validation keys cover every english key except attributes', function () {
    $en = localeValidationLines('en');
    $id = localeValidationLines('id');

    $ignored = ['attributes', 'custom', 'form_messages'];
    $enKeys = array_values(array_diff(array_keys($en), $ignored));
    $idKeys = array_values(array_diff(array_keys($id), $ignored));

    expect(array_diff($enKeys, $idKeys))->toBe([]);
    expect(array_diff(array_keys($en['form_messages'] ?? []), array_keys($id['form_messages'])))->toBe([]);
});

test('indonesian validation file exists and is a php array', function () {
    expect(File::exists(lang_path('id/validation.php')))->toBeTrue();
    expect(localeValidationLines('id'))->toBeArray()->not->toBeEmpty();
});
