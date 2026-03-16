export interface RegionalNumberFormatSettings {
    currency: string;
    number_format_decimal: string;
    number_format_thousand: string;
    number_format_hide_decimal: boolean;
}

const STORAGE_KEY = 'app.regional_number_format_settings';

export const DEFAULT_REGIONAL_NUMBER_FORMAT_SETTINGS: RegionalNumberFormatSettings =
    {
        currency: 'IDR',
        number_format_decimal: ',',
        number_format_thousand: '.',
        number_format_hide_decimal: false,
    };

type GlobalNumberFormatHost = typeof globalThis & {
    __APP_REGIONAL_NUMBER_FORMAT_SETTINGS__?: RegionalNumberFormatSettings;
};

function toBoolean(value: unknown): boolean {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();
        return ['1', 'true', 'yes', 'on'].includes(normalized);
    }

    return false;
}

function normalizeSeparator(
    value: unknown,
    fallback: string,
): string {
    return typeof value === 'string' && value.length > 0 ? value : fallback;
}

function normalizeCurrency(value: unknown, fallback: string): string {
    return typeof value === 'string' && value.length > 0 ? value : fallback;
}

export function normalizeRegionalNumberFormatSettings(
    value?: Partial<RegionalNumberFormatSettings> | null,
): RegionalNumberFormatSettings {
    return {
        currency: normalizeCurrency(
            value?.currency,
            DEFAULT_REGIONAL_NUMBER_FORMAT_SETTINGS.currency,
        ),
        number_format_decimal: normalizeSeparator(
            value?.number_format_decimal,
            DEFAULT_REGIONAL_NUMBER_FORMAT_SETTINGS.number_format_decimal,
        ),
        number_format_thousand: normalizeSeparator(
            value?.number_format_thousand,
            DEFAULT_REGIONAL_NUMBER_FORMAT_SETTINGS.number_format_thousand,
        ),
        number_format_hide_decimal: toBoolean(value?.number_format_hide_decimal),
    };
}

function getGlobalSettings(): RegionalNumberFormatSettings | null {
    const host = globalThis as GlobalNumberFormatHost;

    return host.__APP_REGIONAL_NUMBER_FORMAT_SETTINGS__ ?? null;
}

function setGlobalSettings(
    settings: RegionalNumberFormatSettings,
): RegionalNumberFormatSettings {
    const host = globalThis as GlobalNumberFormatHost;
    host.__APP_REGIONAL_NUMBER_FORMAT_SETTINGS__ = settings;

    return settings;
}

function readStorageSettings(): RegionalNumberFormatSettings | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as Partial<RegionalNumberFormatSettings>;
        return normalizeRegionalNumberFormatSettings(parsed);
    } catch {
        return null;
    }
}

function writeStorageSettings(settings: RegionalNumberFormatSettings): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
}

export function getRegionalNumberFormatSettings(): RegionalNumberFormatSettings {
    const globalSettings = getGlobalSettings();
    if (globalSettings) {
        return globalSettings;
    }

    const storageSettings = readStorageSettings();
    if (storageSettings) {
        return setGlobalSettings(storageSettings);
    }

    return setGlobalSettings(DEFAULT_REGIONAL_NUMBER_FORMAT_SETTINGS);
}

export function setRegionalNumberFormatSettings(
    settings: Partial<RegionalNumberFormatSettings>,
): RegionalNumberFormatSettings {
    const current = getRegionalNumberFormatSettings();
    const normalized = normalizeRegionalNumberFormatSettings({
        ...current,
        ...settings,
    });

    setGlobalSettings(normalized);
    writeStorageSettings(normalized);

    return normalized;
}

function toNumber(value: string | number): number | null {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
}

function resolveFractionDigits(
    hideDecimal: boolean,
    minimumFractionDigits?: number,
    maximumFractionDigits?: number,
): Pick<Intl.NumberFormatOptions, 'minimumFractionDigits' | 'maximumFractionDigits'> | {} {
    if (hideDecimal) {
        return {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        };
    }

    if (
        typeof minimumFractionDigits !== 'number' &&
        typeof maximumFractionDigits !== 'number'
    ) {
        return {};
    }

    const min = Math.max(0, minimumFractionDigits ?? 0);
    const max = Math.max(min, maximumFractionDigits ?? min);

    return {
        minimumFractionDigits: min,
        maximumFractionDigits: max,
    };
}

function applyRegionalSeparators(
    value: number,
    formatter: Intl.NumberFormat,
    settings: RegionalNumberFormatSettings,
): string {
    const parts = formatter.formatToParts(value);

    return parts
        .map((part) => {
            if (part.type === 'group') {
                return settings.number_format_thousand;
            }

            if (part.type === 'decimal') {
                return settings.number_format_hide_decimal
                    ? ''
                    : settings.number_format_decimal;
            }

            if (
                settings.number_format_hide_decimal &&
                part.type === 'fraction'
            ) {
                return '';
            }

            return part.value;
        })
        .join('');
}

export interface RegionalNumberFormatOptions {
    locale?: string;
    minimumFractionDigits?: number;
    maximumFractionDigits?: number;
    useGrouping?: boolean;
}

export interface RegionalCurrencyFormatOptions
    extends RegionalNumberFormatOptions {
    currency?: string;
    currencyDisplay?: Intl.NumberFormatOptions['currencyDisplay'];
}

export function formatNumberByRegionalSettings(
    value: string | number,
    {
        locale = 'id-ID',
        minimumFractionDigits = 0,
        maximumFractionDigits = 2,
        useGrouping = true,
    }: RegionalNumberFormatOptions = {},
): string {
    const numberValue = toNumber(value);
    if (numberValue === null) {
        return '-';
    }

    const settings = getRegionalNumberFormatSettings();
    const fractionDigits = resolveFractionDigits(
        settings.number_format_hide_decimal,
        minimumFractionDigits,
        maximumFractionDigits,
    );

    const formatter = new Intl.NumberFormat(locale, {
        useGrouping,
        ...fractionDigits,
    });

    return applyRegionalSeparators(numberValue, formatter, settings);
}

export function formatCurrencyByRegionalSettings(
    value: string | number,
    {
        currency,
        currencyDisplay,
        locale = 'id-ID',
        minimumFractionDigits,
        maximumFractionDigits,
        useGrouping = true,
    }: RegionalCurrencyFormatOptions = {},
): string {
    const numberValue = toNumber(value);
    if (numberValue === null) {
        return '-';
    }

    const settings = getRegionalNumberFormatSettings();
    const fractionDigits = resolveFractionDigits(
        settings.number_format_hide_decimal,
        minimumFractionDigits,
        maximumFractionDigits,
    );

    const formatter = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency ?? settings.currency,
        currencyDisplay,
        useGrouping,
        ...fractionDigits,
    });

    return applyRegionalSeparators(numberValue, formatter, settings);
}
