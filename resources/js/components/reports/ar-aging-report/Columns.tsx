'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import {
    FORMAT_LOCALE,
    formatDate as formatDateValue,
} from '@/utils/formatters';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ArAgingReportItem = {
    customer_invoice: {
        id: number;
        invoice_number: string | null;
        invoice_date: string | null;
        due_date: string | null;
        status: string;
    };
    customer: {
        id: number;
        name: string;
    };
    branch: {
        id: number;
        name: string;
    };
    amounts: {
        grand_total: string;
        amount_received: string;
        credit_note_amount: string;
        amount_due: string;
    };
    aging_buckets: {
        current: string;
        '1_30': string;
        '31_60': string;
        '61_90': string;
        over_90: string;
    };
};

function formatDate(value: string | null | undefined): string {
    return formatDateValue(value);
}

function formatCurrency(value: string | null | undefined): string {
    if (!value) return '-';
    return formatCurrencyByRegionalSettings(value, {
        locale: FORMAT_LOCALE,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

export const arAgingReportColumns: ColumnDef<ArAgingReportItem>[] = [
    createReportSummaryColumn<ArAgingReportItem>({
        accessorKey: 'customer_invoice.invoice_number',
        header: { key: 'column.invoice_number', fallback: 'Invoice Number' },
        getPrimary: (item) => item.customer_invoice?.invoice_number,
        getSecondary: (item) => formatDate(item.customer_invoice?.invoice_date),
        sortable: true,
    }),
    createReportTextColumn<ArAgingReportItem>({
        accessorKey: 'customer.name',
        header: { key: 'column.customer', fallback: 'Customer' },
        getValue: (item) => item.customer?.name,
        sortable: true,
    }),
    createReportTextColumn<ArAgingReportItem>({
        accessorKey: 'branch.name',
        header: { key: 'column.branch', fallback: 'Branch' },
        getValue: (item) => item.branch?.name,
        sortable: true,
    }),
    createReportStatusBadgeColumn<ArAgingReportItem>({
        accessorKey: 'customer_invoice.status',
        header: { key: 'column.status', fallback: 'Status' },
        getValue: (item) => item.customer_invoice?.status,
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: 'grand_total',
        header: { key: 'column.grand_total', fallback: 'Grand Total' },
        align: 'right',
        getValue: (item) => formatCurrency(item.amounts.grand_total),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: 'amount_received',
        header: { key: 'column.amount_received', fallback: 'Amount Received' },
        align: 'right',
        getValue: (item) => formatCurrency(item.amounts.amount_received),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: 'credit_note_amount',
        header: {
            key: 'column.credit_note_amount',
            fallback: 'Credit Note Amount',
        },
        align: 'right',
        getValue: (item) => formatCurrency(item.amounts.credit_note_amount),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: 'amount_due',
        header: { key: 'column.amount_due', fallback: 'Amount Due' },
        align: 'right',
        getValue: (item) => formatCurrency(item.amounts.amount_due),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: 'current',
        header: { key: 'column.current', fallback: 'Current' },
        align: 'right',
        getValue: (item) => formatCurrency(item.aging_buckets.current),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: '1_30',
        header: { key: 'column.days_1_30', fallback: '1-30 Days' },
        align: 'right',
        getValue: (item) => formatCurrency(item.aging_buckets['1_30']),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: '31_60',
        header: { key: 'column.days_31_60', fallback: '31-60 Days' },
        align: 'right',
        getValue: (item) => formatCurrency(item.aging_buckets['31_60']),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: '61_90',
        header: { key: 'column.days_61_90', fallback: '61-90 Days' },
        align: 'right',
        getValue: (item) => formatCurrency(item.aging_buckets['61_90']),
    }),
    createReportTextColumn<ArAgingReportItem>({
        id: 'over_90',
        header: { key: 'column.days_over_90', fallback: 'Over 90 Days' },
        align: 'right',
        getValue: (item) => formatCurrency(item.aging_buckets.over_90),
    }),
];
