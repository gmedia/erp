const LOCALE_KEY = 'app_locale';
export const DEFAULT_LOCALE = 'en';
export const SUPPORTED_LOCALES = ['en', 'id'] as const;

function isSupported(value: string | null): value is string {
    return (
        value !== null &&
        (SUPPORTED_LOCALES as readonly string[]).includes(value)
    );
}

/**
 * Locale yang dipilih pengguna. Dibaca sinkron dari localStorage sehingga
 * request API pertama sudah membawa header yang benar.
 */
export function getStoredLocale(): string {
    if (typeof localStorage === 'undefined') {
        return DEFAULT_LOCALE;
    }

    const stored = localStorage.getItem(LOCALE_KEY);

    return isSupported(stored) ? stored : DEFAULT_LOCALE;
}

export function setStoredLocale(locale: string): void {
    if (!isSupported(locale)) {
        return;
    }

    localStorage.setItem(LOCALE_KEY, locale);
}
