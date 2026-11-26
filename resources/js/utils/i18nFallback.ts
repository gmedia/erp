import { useOptionalTranslation } from '@/contexts/i18n-context';
import { useCallback } from 'react';

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

export function useToastTranslator(
    namespace: string,
): (key: string, fallback: string) => string {
    const { t } = useOptionalTranslation();

    return useCallback(
        (key: string, fallback: string) =>
            translateOr(t, `${namespace}.${key}`, fallback),
        [t, namespace],
    );
}

export function useHookToast(): (key: string, fallback: string) => string {
    return useToastTranslator('hook_toast');
}
