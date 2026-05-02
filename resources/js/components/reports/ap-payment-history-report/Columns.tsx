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
            id: 'col',
            header: 'Payment Number',
            getPrimary: (item) => item.payment?.number,
            getSecondary: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            header: 'Supplier',
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            header: 'Branch',
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            header: 'Payment Date',
            getValue: (item) => formatDate(item.payment?.date),
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            header: 'Payment Method',
            getValue: (item) => item.payment?.method,
            sortable: true,
        }),
        createReportTextColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            header: 'Bank Account',
            getValue: (item) => item.bank_account?.name,
            sortable: true,
        }),
        createCurrencyColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            label: 'Total Amount',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            label: 'Allocated Amount',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<ApPaymentHistoryReportItem>({
            id: 'col',
            label: 'Unallocated Amount',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createReportStatusBadgeColumn<ApPaymentHistoryReportItem>({
            id: 'status',
            header: 'Status',
            getValue: (item) => item.payment?.status,
        }),
    ];