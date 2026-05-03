'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
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
    return formatDateByRegionalSettings(value);
}

function formatCurrency(value: string | null | undefined): string {
    if (!value) return '-';
    return formatCurrencyByRegionalSettings(value, {
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export const arAgingReportColumns: ColumnDef<ArAgingReportItem>[] = [
    createReportSummaryColumn<ArAgingReportItem>({
        accessorKey: 'customer_invoice.invoice_number',
        header: 'Invoice Number',
        getPrimary: (item) => item.customer_invoice?.invoice_number,
        getSecondary: (item) => formatDate(item.customer_invoice?.invoice_date),
        sortable: true,
    }),
    createReportTextColumn<ArAgingReportItem>({
        accessorKey: 'customer.name',
        header: 'Customer',
        getValue: (item) => item.customer?.name,
        sortable: true,
    }),
    createReportTextColumn<ArAgingReportItem>({
        accessorKey: 'branch.name',
        header: 'Branch',
        getValue: (item) => item.branch?.name,
        sortable: true,
    }),
    createReportStatusBadgeColumn<ArAgingReportItem>({
        accessorKey: 'customer_invoice.status',
        header: 'Status',
        getValue: (item) => item.customer_invoice?.status,
    }),
    {
        id: 'grand_total',
        header: 'Grand Total',
        cell: ({ row }) => formatCurrency(row.original.amounts.grand_total),
    },
    {
        id: 'amount_received',
        header: 'Amount Received',
        cell: ({ row }) => formatCurrency(row.original.amounts.amount_received),
    },
    {
        id: 'credit_note_amount',
        header: 'Credit Note Amount',
        cell: ({ row }) =>
            formatCurrency(row.original.amounts.credit_note_amount),
    },
    {
        id: 'amount_due',
        header: 'Amount Due',
        cell: ({ row }) => formatCurrency(row.original.amounts.amount_due),
    },
    {
        id: 'current',
        header: 'Current',
        cell: ({ row }) => formatCurrency(row.original.aging_buckets.current),
    },
    {
        id: '1_30',
        header: '1-30 Days',
        cell: ({ row }) => formatCurrency(row.original.aging_buckets['1_30']),
    },
    {
        id: '31_60',
        header: '31-60 Days',
        cell: ({ row }) => formatCurrency(row.original.aging_buckets['31_60']),
    },
    {
        id: '61_90',
        header: '61-90 Days',
        cell: ({ row }) => formatCurrency(row.original.aging_buckets['61_90']),
    },
    {
        id: 'over_90',
        header: 'Over 90 Days',
        cell: ({ row }) => formatCurrency(row.original.aging_buckets.over_90),
    },
];
