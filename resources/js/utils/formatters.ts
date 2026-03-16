import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';

/**
 * Format a numeric value as currency.
 *
 * @param value - The value to format (string or number)
 * @param currency - Optional currency code override
 * @param locale - The locale for formatting (default: 'id-ID')
 * @returns Formatted currency string, or '-' if invalid
 */
export function formatCurrency(
    value: string | number,
    currency?: string,
    locale: string = 'id-ID',
): string {
    return formatCurrencyByRegionalSettings(value, {
        currency,
        locale,
    });
}

export function formatRupiah(value: string | number): string {
    return formatCurrencyByRegionalSettings(value, {
        locale: 'id-ID',
    });
}

export function formatNumber(
    value: string | number,
    locale: string = 'id-ID',
): string {
    return formatNumberByRegionalSettings(value, { locale });
}
