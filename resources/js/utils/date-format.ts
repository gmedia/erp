import { format } from 'date-fns';

export interface RegionalDateFormatSettings {
    date_format: string;
}

const STORAGE_KEY = 'app.regional_date_format_settings';

export const DEFAULT_REGIONAL_DATE_FORMAT_SETTINGS: RegionalDateFormatSettings =
    {
        date_format: 'd/m/Y',
    };

type GlobalDateFormatHost = typeof globalThis & {
    __APP_REGIONAL_DATE_FORMAT_SETTINGS__?: RegionalDateFormatSettings;
};

function normalizeDateFormat(value: unknown, fallback: string): string {
    if (typeof value !== 'string') {
        return fallback;
    }

    const normalized = value.trim();

    return normalized.length > 0 ? normalized : fallback;
}

export function normalizeRegionalDateFormatSettings(
    value?: Partial<RegionalDateFormatSettings> | null,
): RegionalDateFormatSettings {
    return {
        date_format: normalizeDateFormat(
            value?.date_format,
            DEFAULT_REGIONAL_DATE_FORMAT_SETTINGS.date_format,
        ),
    };
}

function getGlobalSettings(): RegionalDateFormatSettings | null {
    const host = globalThis as GlobalDateFormatHost;

    return host.__APP_REGIONAL_DATE_FORMAT_SETTINGS__ ?? null;
}

function setGlobalSettings(
    settings: RegionalDateFormatSettings,
): RegionalDateFormatSettings {
    const host = globalThis as GlobalDateFormatHost;
    host.__APP_REGIONAL_DATE_FORMAT_SETTINGS__ = settings;

    return settings;
}

function readStorageSettings(): RegionalDateFormatSettings | null {
    if (globalThis.window === undefined) {
        return null;
    }

    const raw = globalThis.localStorage.getItem(STORAGE_KEY);
    if (!raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as Partial<RegionalDateFormatSettings>;
        return normalizeRegionalDateFormatSettings(parsed);
    } catch {
        return null;
    }
}

function writeStorageSettings(settings: RegionalDateFormatSettings): void {
    if (globalThis.window === undefined) {
        return;
    }

    globalThis.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
}

export function getRegionalDateFormatSettings(): RegionalDateFormatSettings {
    const globalSettings = getGlobalSettings();
    if (globalSettings) {
        return globalSettings;
    }

    const storageSettings = readStorageSettings();
    if (storageSettings) {
        return setGlobalSettings(storageSettings);
    }

    return setGlobalSettings(DEFAULT_REGIONAL_DATE_FORMAT_SETTINGS);
}

export function setRegionalDateFormatSettings(
    settings: Partial<RegionalDateFormatSettings>,
): RegionalDateFormatSettings {
    const current = getRegionalDateFormatSettings();
    const normalized = normalizeRegionalDateFormatSettings({
        ...current,
        ...settings,
    });

    setGlobalSettings(normalized);
    writeStorageSettings(normalized);

    return normalized;
}

function toDate(value: string | Date | number): Date | null {
    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function toDateInputValue(value?: string | Date | null): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const date = value instanceof Date ? value : new Date(value);

    return Number.isNaN(date.getTime()) ? '' : format(date, 'yyyy-MM-dd');
}

function to12Hour(hours: number): number {
    const normalized = hours % 12;

    return normalized === 0 ? 12 : normalized;
}

function pad2(value: number): string {
    return String(value).padStart(2, '0');
}

function formatClockTime(
    date: Date,
    hour12: boolean,
    withSeconds: boolean,
): string {
    const hours = date.getHours();
    const hourPart = pad2(hour12 ? to12Hour(hours) : hours);
    const minutePart = pad2(date.getMinutes());
    const secondPart = withSeconds ? `:${pad2(date.getSeconds())}` : '';

    if (!hour12) {
        return `${hourPart}:${minutePart}${secondPart}`;
    }

    return `${hourPart}:${minutePart}${secondPart} ${hours >= 12 ? 'PM' : 'AM'}`;
}

function formatDatePattern(date: Date, format: string, locale: string): string {
    const monthShort = new Intl.DateTimeFormat(locale, {
        month: 'short',
    }).format(date);
    const monthLong = new Intl.DateTimeFormat(locale, {
        month: 'long',
    }).format(date);
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    const hours = date.getHours();
    const minutes = date.getMinutes();
    const seconds = date.getSeconds();

    const tokenMap: Record<string, string> = {
        d: pad2(day),
        j: String(day),
        m: pad2(month),
        n: String(month),
        Y: String(year),
        y: String(year).slice(-2),
        M: monthShort,
        F: monthLong,
        H: pad2(hours),
        h: pad2(to12Hour(hours)),
        i: pad2(minutes),
        s: pad2(seconds),
        A: hours >= 12 ? 'PM' : 'AM',
        a: hours >= 12 ? 'pm' : 'am',
    };

    let escaped = false;
    let output = '';

    for (const char of format) {
        if (escaped) {
            output += char;
            escaped = false;
            continue;
        }

        if (char === '\\') {
            escaped = true;
            continue;
        }

        output += tokenMap[char] ?? char;
    }

    return output;
}

export interface RegionalDateFormatOptions {
    dateFormat?: string;
    locale?: string;
    fallback?: string;
}

type DateTimeInput = string | Date | number | null | undefined;

export function formatDateByRegionalSettings(
    value: DateTimeInput,
    {
        dateFormat,
        locale = 'id-ID',
        fallback = '-',
    }: RegionalDateFormatOptions = {},
): string {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const date = toDate(value);
    if (!date) {
        return fallback;
    }

    const settings = getRegionalDateFormatSettings();
    const resolvedFormat = normalizeDateFormat(
        dateFormat,
        settings.date_format,
    );

    return formatDatePattern(date, resolvedFormat, locale);
}

export interface RegionalDateTimeFormatOptions extends RegionalDateFormatOptions {
    hour12?: boolean;
    withSeconds?: boolean;
}

export interface RegionalTimeFormatOptions {
    locale?: string;
    fallback?: string;
    hour12?: boolean;
    withSeconds?: boolean;
}

export function formatTimeByRegionalSettings(
    value: DateTimeInput,
    {
        fallback = '-',
        hour12 = false,
        withSeconds = false,
    }: RegionalTimeFormatOptions = {},
): string {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const date = toDate(value);
    if (!date) {
        return fallback;
    }

    return formatClockTime(date, hour12, withSeconds);
}

/** Tanggal+waktu, tetapi nilai tengah malam (date-only) tampil tanpa jam. */
export function formatDateTimeOrDateByRegionalSettings(
    value: DateTimeInput,
    options: RegionalDateTimeFormatOptions = {},
): string {
    if (value === null || value === undefined || value === '') {
        return options.fallback ?? '-';
    }

    const date = toDate(value);
    if (!date) {
        return options.fallback ?? '-';
    }

    const isDateOnly =
        date.getHours() === 0 &&
        date.getMinutes() === 0 &&
        date.getSeconds() === 0;

    if (isDateOnly) {
        return formatDateByRegionalSettings(date, options);
    }

    return formatDateTimeByRegionalSettings(date, options);
}

export function formatDateTimeByRegionalSettings(
    value: DateTimeInput,
    {
        dateFormat,
        locale = 'id-ID',
        fallback = '-',
        hour12 = false,
        withSeconds = false,
    }: RegionalDateTimeFormatOptions = {},
): string {
    if (value === null || value === undefined || value === '') {
        return fallback;
    }

    const date = toDate(value);
    if (!date) {
        return fallback;
    }

    const datePart = formatDateByRegionalSettings(date, {
        dateFormat,
        locale,
        fallback,
    });

    const timePart = formatClockTime(date, hour12, withSeconds);

    return `${datePart} ${timePart}`;
}
