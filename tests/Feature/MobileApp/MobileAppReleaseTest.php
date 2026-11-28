<?php

use App\Models\MobileAppRelease;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\post;

uses(RefreshDatabase::class)->group('mobile-app');

function mobileAppTestApkContent(): string
{
    $path = tempnam(sys_get_temp_dir(), 'apk');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('AndroidManifest.xml', '<manifest package="com.example.app" />');
    $zip->close();

    $content = (string) file_get_contents($path);
    @unlink($path);

    return $content;
}

function mobileAppTestRelease(int $versionCode, bool $active = false, string $versionName = '1.0.0'): MobileAppRelease
{
    return MobileAppRelease::create([
        'version_name' => $versionName,
        'version_code' => $versionCode,
        'min_supported_version_code' => 1,
        'notes' => 'Release notes',
        'file_path' => 'mobile-app-releases/' . $versionCode . '.apk',
        'file_size' => 1024,
        'sha256' => str_repeat('a', 64),
        'is_active' => $active,
    ]);
}

beforeEach(function () {
    Storage::fake('local');

    Setting::create([
        'group' => 'general',
        'key' => 'company_name',
        'value' => 'PT Contoh Nusantara',
        'type' => 'string',
    ]);

    $this->admin = createTestUserWithPermissions(['mobile_app_release']);
});

it('uploads a valid apk and stores metadata', function () {
    $content = mobileAppTestApkContent();
    $file = UploadedFile::fake()->createWithContent('app-release.apk', $content);

    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => $file,
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
        'notes' => 'Rilis perdana',
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.version_name', '1.0.0')
        ->assertJsonPath('data.version_code', 1)
        ->assertJsonPath('data.is_active', false);

    $release = MobileAppRelease::query()->firstOrFail();

    expect($release->sha256)->toBe(hash('sha256', $content))
        ->and($release->file_size)->toBe(strlen($content));

    Storage::disk('local')->assertExists($release->file_path);
});

it('rejects a file without the apk extension', function () {
    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->createWithContent('app-release.txt', mobileAppTestApkContent()),
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects a file whose content is not an apk package', function () {
    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->createWithContent('app-release.apk', 'not an android package'),
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects an upload larger than the configured limit', function () {
    Setting::create([
        'group' => 'mobile_app',
        'key' => 'mobile_app_max_upload_mb',
        'value' => '1',
        'type' => 'integer',
    ]);

    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->create('app-release.apk', 2048),
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('requires the version code to be greater than the active release', function () {
    mobileAppTestRelease(10, true);

    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->createWithContent('app-release.apk', mobileAppTestApkContent()),
        'version_name' => '1.0.0',
        'version_code' => 10,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('version_code');
});

it('accepts a version code greater than the active release', function () {
    mobileAppTestRelease(10, true);

    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->createWithContent('app-release.apk', mobileAppTestApkContent()),
        'version_name' => '1.1.0',
        'version_code' => 11,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.version_code', 11);
});

it('forbids release management without the mobile_app_release permission', function () {
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider, ['*']);

    getJson('/api/mobile-app-releases')->assertForbidden();

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->createWithContent('app-release.apk', mobileAppTestApkContent()),
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])->assertForbidden();
});

it('requires authentication to download the apk', function () {
    mobileAppTestRelease(1, true);

    getJson('/api/mobile-app/download')->assertUnauthorized();
});

it('downloads the active release with a company and version filename', function () {
    $release = mobileAppTestRelease(1, true, '1.0.0');
    Storage::disk('local')->put($release->file_path, mobileAppTestApkContent());

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    get('/api/mobile-app/download')
        ->assertOk()
        ->assertDownload('pt-contoh-nusantara-1.0.0.apk');
});

it('exposes the latest release publicly', function () {
    mobileAppTestRelease(7, true, '1.4.0');

    getJson('/api/mobile-app/latest')
        ->assertOk()
        ->assertJsonPath('data.version_name', '1.4.0')
        ->assertJsonPath('data.version_code', 7)
        ->assertJsonPath('data.min_supported_version_code', 1)
        ->assertJsonPath('data.sha256', str_repeat('a', 64))
        ->assertJsonPath('data.download_page_url', url('/mobile-app'));
});

it('returns not found for the latest release when none is active', function () {
    getJson('/api/mobile-app/latest')->assertNotFound();
});

it('returns an empty state for the download page when no release is active', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    getJson('/api/mobile-app')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('activates a single release and deactivates it again', function () {
    $first = mobileAppTestRelease(1, true, '1.0.0');
    $second = mobileAppTestRelease(2, false, '1.1.0');

    Sanctum::actingAs($this->admin, ['*']);

    patchJson("/api/mobile-app-releases/{$second->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.is_active', true);

    expect($first->fresh()->is_active)->toBeFalse()
        ->and($second->fresh()->is_active)->toBeTrue();

    patchJson("/api/mobile-app-releases/{$second->id}/deactivate")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($second->fresh()->is_active)->toBeFalse();
});

it('lists releases with search, active filter, and pagination', function () {
    mobileAppTestRelease(1, true, '1.0.0');
    mobileAppTestRelease(2, false, '2.0.0');

    Sanctum::actingAs($this->admin, ['*']);

    getJson('/api/mobile-app-releases?search=2.0.0&per_page=10')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.version_name', '2.0.0');

    getJson('/api/mobile-app-releases?is_active=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.version_code', 1);

    getJson('/api/mobile-app-releases?per_page=1')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2);
});

it('validates the release index filters', function () {
    Sanctum::actingAs($this->admin, ['*']);

    getJson('/api/mobile-app-releases?per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('requires the apk file on upload', function () {
    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects an apk whose declared MIME type is not allowed', function () {
    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()
            ->createWithContent('app-release.apk', mobileAppTestApkContent())
            ->mimeType('text/plain'),
        'version_name' => '1.0.0',
        'version_code' => 1,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects a duplicate version code', function () {
    mobileAppTestRelease(5, false, '0.9.0');

    Sanctum::actingAs($this->admin, ['*']);

    post('/api/mobile-app-releases', [
        'file' => UploadedFile::fake()->createWithContent('app-release.apk', mobileAppTestApkContent()),
        'version_name' => '1.0.0',
        'version_code' => 5,
        'min_supported_version_code' => 1,
    ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('version_code');
});

it('returns not found when the active release file is missing', function () {
    mobileAppTestRelease(1, true);

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    getJson('/api/mobile-app/download')->assertNotFound();
});
