import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Utility to format dates consistently across the app
export const formatDate = (value: string | Date) =>
    new Date(value).toLocaleDateString();

export const formatCurrency = (value: number) =>
    formatCurrencyByRegionalSettings(value, {
        locale: 'id-ID',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
