import { formatNumber } from './formatters';

const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'] as const;

/**
 * Formats a byte count into a compact human readable string using the
 * company number format (e.g. "12,4 MB" for the Indonesian locale).
 */
export function formatFileSize(bytes: number): string {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }

    const exponent = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        UNITS.length - 1,
    );
    const value = bytes / 1024 ** exponent;
    const decimals = exponent === 0 ? 0 : value >= 10 ? 1 : 2;

    const formatted = formatNumber(value, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    return `${formatted} ${UNITS[exponent]}`;
}
