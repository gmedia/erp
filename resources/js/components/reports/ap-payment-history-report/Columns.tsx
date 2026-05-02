'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
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
    return formatCurrencyByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export const apPaymentHistoryReportColumns: ColumnDef<ApPaymentHistoryReportItem>[] =
    [
        createReportSummaryColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.number',
            header: 'Payment Number',
            getPrimary: (item) => item.payment?.number,
            getSecondary: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'supplier.name',
            header: 'Supplier',
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'branch.name',
            header: 'Branch',
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.date',
            header: 'Payment Date',
            getValue: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.method',
            header: 'Payment Method',
            getValue: (item) => item.payment?.method,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'bank_account',
            header: 'Bank Account',
            getValue: (item) => item.bank_account?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'total_amount',
            header: 'Total Amount',
            getValue: (item) => formatAmount(item.amounts?.total),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'allocated',
            header: 'Allocated',
            getValue: (item) => formatAmount(item.amounts?.allocated),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'unallocated',
            header: 'Unallocated',
            getValue: (item) => formatAmount(item.amounts?.unallocated),
        }),
        createReportStatusBadgeColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.status',
            header: 'Status',
            getValue: (item) => item.payment?.status,
        }),
    ];
