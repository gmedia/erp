<?php

use App\Http\Middleware\ContentSecurityPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses()->group('security-csp');

beforeEach(function () {
    // S3 client needs credentials to be built; url() never calls the network.
    config()->set('filesystems.disks.s3.key', 'test-key');
    config()->set('filesystems.disks.s3.secret', 'test-secret');
    config()->set('filesystems.disks.s3.region', 'us-east-1');
    config()->set('filesystems.disks.s3.bucket', 'erp-bucket');

    Storage::forgetDisk(['local', 'public', 's3', 'broken']);
});

/**
 * Run the CSP middleware and return the img-src directive.
 */
function cspImgSrc(): string
{
    $response = (new ContentSecurityPolicy)->handle(
        Request::create('/'),
        fn () => response('ok'),
    );

    $policy = (string) $response->headers->get('Content-Security-Policy');

    foreach (explode(';', $policy) as $directive) {
        $directive = trim($directive);

        if (str_starts_with($directive, 'img-src ')) {
            return $directive;
        }
    }

    return '';
}

test('local disk without url keeps img-src limited to self and data', function () {
    config()->set('filesystems.default', 'local');

    expect(cspImgSrc())->toBe("img-src 'self' data:");
});

test('s3 disk adds only the origin of its configured url to img-src', function () {
    config()->set('filesystems.default', 's3');
    config()->set('filesystems.disks.s3.url', 'https://objects.example.com/erp-bucket');
    Storage::forgetDisk('s3');

    expect(cspImgSrc())->toBe("img-src 'self' data: https://objects.example.com");
});

test('custom public disk url adds its origin, not the full path', function () {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.url', 'https://files.example.com:8443/storage');
    Storage::forgetDisk('public');

    expect(cspImgSrc())->toBe("img-src 'self' data: https://files.example.com:8443");
});

test('invalid disk url adds no extra origin', function () {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.url', 'not a url');
    Storage::forgetDisk('public');

    $imgSrc = cspImgSrc();

    expect($imgSrc)->toBe("img-src 'self' data:")
        ->and($imgSrc)->not->toContain('*')
        ->and($imgSrc)->not->toContain('https:');
});

test('non http disk url never adds a scheme wildcard', function () {
    config()->set('filesystems.default', 'public');
    config()->set('filesystems.disks.public.url', 'javascript://evil.example.com/storage');
    Storage::forgetDisk('public');

    expect(cspImgSrc())->toBe("img-src 'self' data:");
});

test('s3 disk without configured url uses the endpoint origin', function () {
    config()->set('filesystems.default', 's3');
    config()->set('filesystems.disks.s3.url', null);
    config()->set('filesystems.disks.s3.endpoint', 'https://minio.example.com:9000');
    config()->set('filesystems.disks.s3.use_path_style_endpoint', true);
    config()->set('filesystems.disks.s3.bucket', 'erp-bucket');
    Storage::forgetDisk('s3');

    expect(cspImgSrc())->toBe("img-src 'self' data: https://minio.example.com:9000");
});

test('s3 disk with virtual-host style endpoint uses the bucket host origin', function () {
    config()->set('filesystems.default', 's3');
    config()->set('filesystems.disks.s3.url', null);
    config()->set('filesystems.disks.s3.endpoint', null);
    config()->set('filesystems.disks.s3.use_path_style_endpoint', false);
    Storage::forgetDisk('s3');

    $imgSrc = cspImgSrc();

    expect($imgSrc)->toStartWith("img-src 'self' data: https://erp-bucket.s3.")
        ->and($imgSrc)->not->toContain('*');
});

test('disk that fails to build adds no origin and does not break the policy', function () {
    config()->set('filesystems.default', 'broken');
    config()->set('filesystems.disks.broken', ['driver' => 'unsupported-driver']);
    Storage::forgetDisk('broken');

    expect(cspImgSrc())->toBe("img-src 'self' data:");
});
