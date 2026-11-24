<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Resolves the locale from the explicit X-Locale header, then from the
     * Accept-Language header (q-values honoured), then falls back to the
     * configured default. Only locales listed in app.available_locales are
     * accepted; anything else is ignored. Stateless, so it is safe on the
     * API group (no session or cookie required).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $available = config('app.available_locales', ['en', 'id']);

        $explicit = $this->normalize($request->header('X-Locale'));
        if ($explicit !== null && in_array($explicit, $available, true)) {
            return $explicit;
        }

        foreach ($this->parseAcceptLanguage((string) $request->header('Accept-Language')) as $candidate) {
            $normalized = $this->normalize($candidate);
            if ($normalized !== null && in_array($normalized, $available, true)) {
                return $normalized;
            }
        }

        return config('app.locale');
    }

    /**
     * @return list<string> language tags ordered by descending q-value
     */
    private function parseAcceptLanguage(string $header): array
    {
        if ($header === '') {
            return [];
        }

        $entries = [];
        foreach (explode(',', $header) as $index => $part) {
            [$tag, $q] = array_pad(explode(';q=', trim($part), 2), 2, '1');
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }
            $entries[] = ['tag' => $tag, 'q' => (float) $q, 'index' => $index];
        }

        usort($entries, fn (array $a, array $b) => [$b['q'], $a['index']] <=> [$a['q'], $b['index']]);

        return array_column($entries, 'tag');
    }

    /**
     * Reduce a tag such as "id-ID" or "en_US" to its primary subtag ("id", "en").
     */
    private function normalize(?string $tag): ?string
    {
        if ($tag === null || $tag === '') {
            return null;
        }

        return strtolower(preg_split('/[-_]/', trim($tag))[0]);
    }
}
