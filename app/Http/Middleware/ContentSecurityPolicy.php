<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->buildPolicy());

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    protected function buildPolicy(): string
    {
        $connectSrc = "'self'";
        $scriptSrc = "'self' 'unsafe-inline'";

        if (app()->environment('local', 'testing')) {
            $connectSrc .= ' ws: http://localhost:*';
            $scriptSrc .= ' http://localhost:*';
        }

        return implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline'",
            'img-src ' . implode(' ', array_filter([
                "'self'",
                'data:',
                $this->storageImageOrigin(),
            ])),
            "font-src 'self' data:",
            "connect-src {$connectSrc}",
            "frame-src 'none'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "manifest-src 'self'",
        ]);
    }

    /**
     * Origin (scheme://host[:port]) of the active storage disk, so images
     * served from object storage (e.g. S3/MinIO) are allowed. Returns null
     * when the disk has no usable http(s) URL, so no wildcard is ever added.
     */
    protected function storageImageOrigin(): ?string
    {
        $diskName = (string) config('filesystems.default', 'local');
        $url = config("filesystems.disks.{$diskName}.url");

        if (! is_string($url) || $url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (
            ! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])
        ) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$parts['scheme']}://{$parts['host']}{$port}";
    }
}
