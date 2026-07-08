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
            "img-src 'self' data:",
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
}
