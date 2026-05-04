import type { ViewModalItemsTableColumn } from '@/components/common/ViewModalItemsTable';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';

export type FormatValueInput = string | number | null | undefined;

interface PricingItem {
    quantity: string;
    unit_price: string;
    discount_percent: string;
    tax_percent: string;
    line_total: string;
}

export function createPricingColumns<T extends PricingItem>(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<T>[] {
    return [
        {
            key: 'quantity',
            header: 'Qty',
            align: 'right',
            render: (item) => formatQuantity(item.quantity),
        },
        {
            key: 'unit_price',
            header: 'Unit Price',
            align: 'right',
            render: (item) => formatAmount(item.unit_price),
        },
        {
            key: 'discount_percent',
            header: 'Disc %',
            align: 'right',
            render: (item) => formatPercent(item.discount_percent),
        },
        {
            key: 'tax_percent',
            header: 'Tax %',
            align: 'right',
            render: (item) => formatPercent(item.tax_percent),
        },
        {
            key: 'line_total',
            header: 'Line Total',
            align: 'right',
            render: (item) => formatAmount(item.line_total),
        },
    ];
}

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
