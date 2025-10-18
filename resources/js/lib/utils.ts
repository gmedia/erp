import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

// Utility to format dates consistently across the app
export const formatDate = (value: string | Date) => new Date(value).toLocaleDateString();
