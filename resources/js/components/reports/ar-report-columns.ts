'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import {
    LabelText,
    type TranslatableLabel,
} from '@/components/common/TranslatedText';
import { formatCurrency } from '@/lib/utils';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';
import { createElement } from 'react';

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
    return formatCurrency(value);
}

const MONEY_CELL_CLASS = 'text-right tabular-nums';
const moneyHeader = (label: TranslatableLabel) =>
    createElement(
        'div',
        { className: 'text-right' },
        createElement(LabelText, { label }),
    );
const moneyCell = (value: string | null | undefined) =>
    createElement(
        'div',
        { className: MONEY_CELL_CLASS },
        formatArReportCurrency(value),
    );

export function createArInvoiceBaseColumns<
    T extends ArInvoiceReportBase,
>(): ColumnDef<T>[] {
    return [
        createReportSummaryColumn<T>({
            accessorKey: 'customer_invoice.invoice_number',
            header: {
                key: 'column.invoice_number',
                fallback: 'Invoice Number',
            },
            getPrimary: (item) => item.customer_invoice?.invoice_number,
            getSecondary: (item) =>
                formatArReportDate(item.customer_invoice?.invoice_date),
            sortable: true,
        }),
        createReportTextColumn<T>({
            accessorKey: 'customer.name',
            header: { key: 'column.customer', fallback: 'Customer' },
            getValue: (item) => item.customer?.name,
            sortable: true,
        }),
        createReportTextColumn<T>({
            accessorKey: 'branch.name',
            header: { key: 'column.branch', fallback: 'Branch' },
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportStatusBadgeColumn<T>({
            accessorKey: 'customer_invoice.status',
            header: { key: 'column.status', fallback: 'Status' },
            getValue: (item) => item.customer_invoice?.status,
        }),
        {
            id: 'grand_total',
            header: () =>
                moneyHeader({
                    key: 'column.grand_total',
                    fallback: 'Grand Total',
                }),
            cell: ({ row }) => moneyCell(row.original.amounts.grand_total),
        },
        {
            id: 'amount_received',
            header: () =>
                moneyHeader({
                    key: 'column.amount_received',
                    fallback: 'Amount Received',
                }),
            cell: ({ row }) => moneyCell(row.original.amounts.amount_received),
        },
        {
            id: 'credit_note_amount',
            header: () =>
                moneyHeader({
                    key: 'column.credit_note_amount',
                    fallback: 'Credit Note Amount',
                }),
            cell: ({ row }) =>
                moneyCell(row.original.amounts.credit_note_amount),
        },
        {
            id: 'amount_due',
            header: () =>
                moneyHeader({
                    key: 'column.amount_due',
                    fallback: 'Amount Due',
                }),
            cell: ({ row }) => moneyCell(row.original.amounts.amount_due),
        },
    ];
}
