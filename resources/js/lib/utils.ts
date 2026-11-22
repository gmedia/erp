import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Utility to format dates consistently across the app
export const formatDate = (value: string | Date) =>
    formatDateByRegionalSettings(value);

/** Formatter uang bersama: kebijakan desimal tunggal 0–2 digit; kosong → `-`. */
export const formatCurrency = (
    value: string | number | null | undefined,
): string => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return formatCurrencyByRegionalSettings(value, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};
