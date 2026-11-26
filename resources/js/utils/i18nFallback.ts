/**
 * Returns the translated string for `key`, or `fallback` when the key is
 * missing. `t` returns the key itself for missing translations, so comparing
 * against the key detects the miss.
 */
export function translateOr(
    t: (key: string, params?: Record<string, string>) => string,
    key: string,
    fallback: string,
    params?: Record<string, string>,
): string {
    const translated = t(key, params);

    return translated === key ? fallback : translated;
}
