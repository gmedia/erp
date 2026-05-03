'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import type { ColumnDef } from '@tanstack/react-table';

export interface ArInvoiceReportBase {
    customer_invoice: {
        id: number;
        invoice_number: string | null;
        invoice_date: string | null;
        due_date: string | null;
        status: string;
    };
    customer: { id: number; name: string };
    branch: { id: number; name: string };
    amounts: {
        grand_total: string;
        amount_received: string;
        credit_note_amount: string;
        amount_due: string;
    };
}

export function formatArReportDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

export function formatArReportCurrency(
    value: string | null | undefined,
): string {
    if (!value) return '-';
    return formatCurrencyByRegionalSettings(value, {
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export function createArInvoiceBaseColumns<
    T extends ArInvoiceReportBase,
>(): ColumnDef<T>[] {
    return [
        createReportSummaryColumn<T>({
            accessorKey: 'customer_invoice.invoice_number',
            header: 'Invoice Number',
            getPrimary: (item) => item.customer_invoice?.invoice_number,
            getSecondary: (item) =>
                formatArReportDate(item.customer_invoice?.invoice_date),
            sortable: true,
        }),
        createReportTextColumn<T>({
            accessorKey: 'customer.name',
            header: 'Customer',
            getValue: (item) => item.customer?.name,
            sortable: true,
        }),
        createReportTextColumn<T>({
            accessorKey: 'branch.name',
            header: 'Branch',
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportStatusBadgeColumn<T>({
            accessorKey: 'customer_invoice.status',
            header: 'Status',
            getValue: (item) => item.customer_invoice?.status,
        }),
        {
            id: 'grand_total',
            header: 'Grand Total',
            cell: ({ row }) =>
                formatArReportCurrency(row.original.amounts.grand_total),
        },
        {
            id: 'amount_received',
            header: 'Amount Received',
            cell: ({ row }) =>
                formatArReportCurrency(row.original.amounts.amount_received),
        },
        {
            id: 'credit_note_amount',
            header: 'Credit Note Amount',
            cell: ({ row }) =>
                formatArReportCurrency(row.original.amounts.credit_note_amount),
        },
        {
            id: 'amount_due',
            header: 'Amount Due',
            cell: ({ row }) =>
                formatArReportCurrency(row.original.amounts.amount_due),
        },
    ];
}
