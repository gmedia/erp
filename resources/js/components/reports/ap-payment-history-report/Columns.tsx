'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { createCurrencyColumn } from '@/utils/columns';
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
        notes: string | null;
    };
    supplier: {
        id: number;
        name: string;
    };
    branch: {
        id: number;
        name: string;
    };
    bank_account: {
        id: number;
        name: string;
        account_number: string;
    } | null;
    amounts: {
        total: string;
        allocated: string;
        unallocated: string;
    };
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

export const apPaymentHistoryReportColumns: ColumnDef<ApPaymentHistoryReportItem>[] =
    [
        createReportSummaryColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.number' as any,
            header: 'Payment Number',
            getPrimary: (item) => item.payment?.number,
            getSecondary: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'supplier.name' as any,
            header: 'Supplier',
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'branch.name' as any,
            header: 'Branch',
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.date' as any,
            header: 'Payment Date',
            getValue: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.method' as any,
            header: 'Payment Method',
            getValue: (item) => item.payment?.method,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'bank_account.name' as any,
            header: 'Bank Account',
            getValue: (item) => item.bank_account?.name,
            sortable: true,
        }),
        createCurrencyColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'amounts.total' as any,
            label: 'Total Amount',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'amounts.allocated' as any,
            label: 'Allocated Amount',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'amounts.unallocated' as any,
            label: 'Unallocated Amount',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createReportStatusBadgeColumn<ApPaymentHistoryReportItem>({
            accessorKey: 'payment.status',
            header: 'Status',
            getValue: (item) => item.payment?.status,
        }),
    ];