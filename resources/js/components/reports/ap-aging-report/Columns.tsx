'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { createCurrencyColumn, createNumberColumn } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ApAgingReportItem = {
    id: number;
    bill: {
        number: string;
        supplier_invoice_number: string | null;
        bill_date: string | null;
        due_date: string | null;
        status: string;
        currency: string;
        payment_terms: string | null;
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
    references: {
        purchase_order_number: string | null;
        goods_receipt_number: string | null;
    };
    amounts: {
        grand_total: string;
        amount_paid: string;
        amount_due: string;
    };
    aging_buckets: {
        current: string;
        days_1_30: string;
        days_31_60: string;
        days_61_90: string;
        days_over_90: string;
    };
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

export const apAgingReportColumns: ColumnDef<ApAgingReportItem>[] = [
    createReportSummaryColumn<ApAgingReportItem>({
        accessorKey: 'bill.number' as any,
        header: 'Bill Number',
        getPrimary: (item) => item.bill?.number,
        getSecondary: (item) => formatDate(item.bill?.bill_date),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'supplier.name' as any,
        header: 'Supplier',
        getValue: (item) => item.supplier?.name,
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'branch.name' as any,
        header: 'Branch',
        getValue: (item) => item.branch?.name,
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'bill.due_date' as any,
        header: 'Due Date',
        getValue: (item) => formatDate(item.bill?.due_date),
        sortable: true,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'amounts.grand_total' as any,
        label: 'Grand Total',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'amounts.amount_due' as any,
        label: 'Amount Due',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'aging_buckets.current' as any,
        label: 'Current',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'aging_buckets.days_1_30' as any,
        label: '1-30 Days',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'aging_buckets.days_31_60' as any,
        label: '31-60 Days',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'aging_buckets.days_61_90' as any,
        label: '61-90 Days',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<ApAgingReportItem>({
        accessorKey: 'aging_buckets.days_over_90' as any,
        label: '>90 Days',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createReportStatusBadgeColumn<ApAgingReportItem>({
        accessorKey: 'bill.status',
        header: 'Status',
        getValue: (item) => item.bill?.status,
    }),
];