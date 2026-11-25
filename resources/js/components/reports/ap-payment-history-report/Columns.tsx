'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatStatusLabel } from '@/lib/status-badge';
import { formatCurrency } from '@/lib/utils';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ApPaymentHistoryReportItem = {
    id: number;
    payment: {
        number: string;
        date: string | null;
        method: string;
        currency: string;
        status: string;
        reference: string | null;
    };
    supplier: { id: number; name: string };
    branch: { id: number; name: string };
    bank_account: { id: number; name: string } | null;
    amounts: { total: string; allocated: string; unallocated: string };
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

function formatAmount(value: string | number | null | undefined): string {
    return formatCurrency(value);
}

export const apPaymentHistoryReportColumns: ColumnDef<ApPaymentHistoryReportItem>[] =
    [
        createReportSummaryColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.number',
            header: {
                key: 'column.payment_number',
                fallback: 'Payment Number',
            },
            getPrimary: (item) => item.payment?.number,
            getSecondary: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'supplier.name',
            header: { key: 'column.supplier', fallback: 'Supplier' },
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'branch.name',
            header: { key: 'column.branch', fallback: 'Branch' },
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.date',
            header: { key: 'column.payment_date', fallback: 'Payment Date' },
            getValue: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.method',
            header: {
                key: 'column.payment_method',
                fallback: 'Payment Method',
            },
            getValue: (item) => formatStatusLabel(item.payment?.method),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'bank_account',
            header: { key: 'column.bank_account', fallback: 'Bank Account' },
            getValue: (item) => item.bank_account?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'total_amount',
            header: { key: 'column.total_amount', fallback: 'Total Amount' },
            align: 'right',
            getValue: (item) => formatAmount(item.amounts?.total),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'allocated',
            header: { key: 'column.allocated', fallback: 'Allocated' },
            align: 'right',
            getValue: (item) => formatAmount(item.amounts?.allocated),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'unallocated',
            header: { key: 'column.unallocated', fallback: 'Unallocated' },
            align: 'right',
            getValue: (item) => formatAmount(item.amounts?.unallocated),
        }),
        createReportStatusBadgeColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.status',
            header: { key: 'column.status', fallback: 'Status' },
            getValue: (item) => item.payment?.status,
            getVariant: (item) =>
                item.payment?.status?.toLowerCase() === 'confirmed'
                    ? 'success'
                    : undefined,
        }),
    ];
