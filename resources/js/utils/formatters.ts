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
    const numValue = typeof value === 'number' ? value : parseFloat(value);
    if (isNaN(numValue)) return '-';

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
    }).format(numValue);
}

export function formatRupiah(value: string | number): string {
    return formatCurrency(value, 'IDR', 'id-ID');
}
