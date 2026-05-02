'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import {
    formatCurrencyByRegionalSettings,
    formatNumberByRegionalSettings,
} from '@/utils/number-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ArOutstandingReportItem = {
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
    days_overdue: number;
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

function formatNumber(value: number | null | undefined): string {
    if (value === null || value === undefined) return '-';
    return formatNumberByRegionalSettings(value, {
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
}

export const arOutstandingReportColumns: ColumnDef<ArOutstandingReportItem>[] =
    [
        createReportSummaryColumn<ArOutstandingReportItem>({
            accessorKey: 'customer_invoice.invoice_number',
            header: 'Invoice Number',
            getPrimary: (item) => item.customer_invoice?.invoice_number,
            getSecondary: (item) =>
                formatDate(item.customer_invoice?.invoice_date),
            sortable: true,
        }),
        createReportTextColumn<ArOutstandingReportItem>({
            accessorKey: 'customer.name',
            header: 'Customer',
            getValue: (item) => item.customer?.name,
            sortable: true,
        }),
        createReportTextColumn<ArOutstandingReportItem>({
            accessorKey: 'branch.name',
            header: 'Branch',
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportStatusBadgeColumn<ArOutstandingReportItem>({
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
            cell: ({ row }) =>
                formatCurrency(row.original.amounts.amount_received),
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
            id: 'days_overdue',
            accessorKey: 'days_overdue',
            header: 'Days Overdue',
            cell: ({ row }) => formatNumber(row.original.days_overdue),
        },
    ];
