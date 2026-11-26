import { getStoredLocale } from '../lib/locale-storage';
import {
    formatDateByRegionalSettings,
    formatDateTimeByRegionalSettings,
    formatTimeByRegionalSettings,
    type RegionalDateFormatOptions,
} from './date-format';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from './number-format';

export type FormatInput = string | number | null | undefined;

const EMPTY_VALUE = '-';

/** Angka dengan pemisah dari Setting perusahaan. Kosong/tidak valid → `-`. */
export function formatNumber(
    value: FormatInput,
    options: {
        minimumFractionDigits?: number;
        maximumFractionDigits?: number;
    } = {},
): string {
    if (value === null || value === undefined || value === '') {
        return EMPTY_VALUE;
    }

    return formatNumberByRegionalSettings(value, {
        locale: getStoredLocale(),
        ...options,
    });
}

/**
 * Uang dengan simbol dari Setting `currency`. Bila Setting kosong, angka tampil
 * tanpa simbol (tidak ada fallback mata uang di frontend).
 */
export function formatCurrency(
    value: FormatInput,
    options: { currency?: string; maximumFractionDigits?: number } = {},
): string {
    if (value === null || value === undefined || value === '') {
        return EMPTY_VALUE;
    }

    const { maximumFractionDigits = 2, currency } = options;

    return formatCurrencyByRegionalSettings(value, {
        locale: getStoredLocale(),
        currency,
        minimumFractionDigits: 0,
        maximumFractionDigits,
    });
}

/** Tanggal memakai pola `date_format` dari Setting; nama bulan mengikuti locale UI. */
export function formatDate(
    value: string | Date | number | null | undefined,
    options: Pick<RegionalDateFormatOptions, 'fallback'> = {},
): string {
    return formatDateByRegionalSettings(value, {
        locale: getStoredLocale(),
        ...options,
    });
}

/** Bulan-tahun, mis. `Nov 2026` (en) / `Nov 2026` (id). Pola tetap dari locale. */
export function formatMonth(
    value: string | Date | number | null | undefined,
    options: Pick<RegionalDateFormatOptions, 'fallback'> = {},
): string {
    if (value === null || value === undefined || value === '') {
        return options.fallback ?? EMPTY_VALUE;
    }

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
        return options.fallback ?? EMPTY_VALUE;
    }

    return new Intl.DateTimeFormat(getStoredLocale(), {
        month: 'short',
        year: 'numeric',
    }).format(date);
}

export function formatDateTime(
    value: string | Date | number | null | undefined,
    options: Pick<RegionalDateFormatOptions, 'fallback'> = {},
): string {
    return formatDateTimeByRegionalSettings(value, {
        locale: getStoredLocale(),
        ...options,
    });
}

export function formatTime(
    value: string | Date | number | null | undefined,
    options: { fallback?: string; withSeconds?: boolean } = {},
): string {
    return formatTimeByRegionalSettings(value, options);
}

/** @deprecated Gunakan `formatCurrency`. Dipertahankan sampai pemanggil dimigrasi. */
export function formatRupiah(value: FormatInput): string {
    return formatCurrency(value);
}
