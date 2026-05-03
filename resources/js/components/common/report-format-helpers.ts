import type { ViewModalItemsTableColumn } from '@/components/common/ViewModalItemsTable';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';

type FormatValueInput = string | number | null | undefined;

interface ItemWithPricing {
    quantity?: FormatValueInput;
    unit_price?: FormatValueInput;
    discount_percent?: FormatValueInput;
    tax_percent?: FormatValueInput;
    line_total?: FormatValueInput;
}

export const formatQuantity = (value: FormatValueInput) =>
    formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

export const formatPercent = (value: FormatValueInput) =>
    `${formatNumberByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;

export const createAmountFormatter = (locale = 'id-ID', currency = 'IDR') => {
    return (value: FormatValueInput) =>
        formatCurrencyByRegionalSettings(value ?? 0, {
            locale,
            currency,
        });
};

export function createPricingColumns<T>(
    formatAmount: (value: FormatValueInput) => string,
): ViewModalItemsTableColumn<T>[] {
    return [
        {
            key: 'quantity',
            header: 'Qty',
            align: 'right',
            render: (item) =>
                formatQuantity((item as ItemWithPricing).quantity),
        },
        {
            key: 'unit_price',
            header: 'Unit Price',
            align: 'right',
            render: (item) =>
                formatAmount((item as ItemWithPricing).unit_price),
        },
        {
            key: 'discount_percent',
            header: 'Disc %',
            align: 'right',
            render: (item) =>
                formatPercent((item as ItemWithPricing).discount_percent),
        },
        {
            key: 'tax_percent',
            header: 'Tax %',
            align: 'right',
            render: (item) =>
                formatPercent((item as ItemWithPricing).tax_percent),
        },
        {
            key: 'line_total',
            header: 'Line Total',
            align: 'right',
            render: (item) =>
                formatAmount((item as ItemWithPricing).line_total),
        },
    ];
}
