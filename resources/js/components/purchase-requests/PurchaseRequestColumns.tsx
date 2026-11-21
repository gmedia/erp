'use client';

import { PurchaseRequest } from '@/types/purchase-request';
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
        label: 'PR Number',
    }),
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'department',
        ...createSortingHeader('Department'),
        cell: renderDepartmentCell,
    },
    {
        accessorKey: 'requester',
        ...createSortingHeader('Requester'),
        cell: renderRequesterCell,
    },
    createDateColumn<PurchaseRequest>({
        accessorKey: 'request_date',
        label: 'Request Date',
    }),
    createDateColumn<PurchaseRequest>({
        accessorKey: 'required_date',
        label: 'Required Date',
    }),
    createPriorityBadgeColumn<PurchaseRequest>({
        accessorKey: 'priority',
        label: 'Priority',
    }),
    createStatusBadgeColumn<PurchaseRequest>({
        accessorKey: 'status',
        label: 'Status',
    }),
    createCurrencyColumn<PurchaseRequest>({
        accessorKey: 'estimated_amount',
        label: 'Estimated Amount',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createActionsColumn<PurchaseRequest>(),
];
