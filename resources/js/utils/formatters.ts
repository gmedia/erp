import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';

/**
 * Format a numeric value as currency.
 *
 * @param value - The value to format (string or number)
 * @param currency - The currency code (default: 'USD')
 * @param locale - The locale for formatting (default: 'en-US')
 * @returns Formatted currency string, or '-' if invalid
 */
export function formatCurrency(
    value: string | number,
    currency: string = 'USD',
    locale: string = 'en-US',
): string {
    return formatCurrencyByRegionalSettings(value, {
        currency,
        locale,
    });
}

export function formatRupiah(value: string | number): string {
    return formatCurrencyByRegionalSettings(value, {
        currency: 'IDR',
        locale: 'id-ID',
    });
}

export function formatNumber(
    value: string | number,
    locale: string = 'id-ID',
): string {
    return formatNumberByRegionalSettings(value, { locale });
}
