'use client';

import { PurchaseRequest } from '@/types/purchase-request';
import { FORMAT_LOCALE } from '@/utils/formatters';
import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createPriorityBadgeColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderBranchCell = ({ row }: { row: { original: PurchaseRequest } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderDepartmentCell = ({
    row,
}: {
    row: { original: PurchaseRequest };
}) => <div>{row.original.department?.name ?? '-'}</div>;

const renderRequesterCell = ({
    row,
}: {
    row: { original: PurchaseRequest };
}) => <div>{row.original.requester?.name ?? '-'}</div>;

export const purchaseRequestColumns: ColumnDef<PurchaseRequest>[] = [
    createSelectColumn<PurchaseRequest>(),
    createTextColumn<PurchaseRequest>({
        accessorKey: 'pr_number',
        label: { key: 'column.pr_number', fallback: 'PR Number' },
        primary: true,
    }),
    {
        accessorKey: 'branch',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'department',
        ...createSortingHeader({
            key: 'column.department',
            fallback: 'Department',
        }),
        cell: renderDepartmentCell,
    },
    {
        accessorKey: 'requester',
        ...createSortingHeader({
            key: 'column.requester',
            fallback: 'Requester',
        }),
        cell: renderRequesterCell,
    },
    createDateColumn<PurchaseRequest>({
        accessorKey: 'request_date',
        label: { key: 'column.request_date', fallback: 'Request Date' },
    }),
    createDateColumn<PurchaseRequest>({
        accessorKey: 'required_date',
        label: { key: 'column.required_date', fallback: 'Required Date' },
    }),
    createPriorityBadgeColumn<PurchaseRequest>({
        accessorKey: 'priority',
        label: { key: 'column.priority', fallback: 'Priority' },
    }),
    createStatusBadgeColumn<PurchaseRequest>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    createCurrencyColumn<PurchaseRequest>({
        accessorKey: 'estimated_amount',
        label: {
            key: 'column.estimated_amount',
            fallback: 'Estimated Amount',
        },
        locale: FORMAT_LOCALE,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }),
    createActionsColumn<PurchaseRequest>(),
];
