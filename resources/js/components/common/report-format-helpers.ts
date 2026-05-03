import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';

export type FormatValueInput = string | number | null | undefined;

export function createAmountFormatter(currency?: string) {
    return (value: FormatValueInput) =>
        formatCurrencyByRegionalSettings(value ?? 0, {
            locale: 'id-ID',
            currency: currency || undefined,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
}

export function formatQuantity(value: FormatValueInput) {
    return formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

export function formatPercent(value: FormatValueInput) {
    return `${formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;
}
