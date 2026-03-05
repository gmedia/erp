'use client';

import { Badge } from '@/components/ui/badge';
import { PurchaseRequest } from '@/types/purchase-request';
import {
    createActionsColumn,
    createDateColumn,
    createNumberColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderBranchCell = ({ row }: { row: { original: PurchaseRequest } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderDepartmentCell = ({ row }: { row: { original: PurchaseRequest } }) => (
    <div>{row.original.department?.name ?? '-'}</div>
);

const renderRequesterCell = ({ row }: { row: { original: PurchaseRequest } }) => (
    <div>{row.original.requester?.name ?? '-'}</div>
);

const renderPriorityCell = ({ row }: { row: { original: PurchaseRequest } }) => {
    const priority = row.original.priority;
    const variant =
        priority === 'urgent'
            ? 'destructive'
            : priority === 'high'
                ? 'default'
                : 'secondary';

    return <Badge variant={variant}>{priority.replace('_', ' ')}</Badge>;
};

const renderStatusCell = ({ row }: { row: { original: PurchaseRequest } }) => (
    <Badge variant="outline">{row.original.status.replace('_', ' ')}</Badge>
);

export const purchaseRequestColumns: ColumnDef<PurchaseRequest>[] = [
    createSelectColumn<PurchaseRequest>(),
    createTextColumn<PurchaseRequest>({ accessorKey: 'pr_number', label: 'PR Number' }),
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
    createDateColumn<PurchaseRequest>({ accessorKey: 'request_date', label: 'Request Date' }),
    createDateColumn<PurchaseRequest>({ accessorKey: 'required_date', label: 'Required Date' }),
    {
        accessorKey: 'priority',
        ...createSortingHeader('Priority'),
        cell: renderPriorityCell,
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createNumberColumn<PurchaseRequest>({
        accessorKey: 'estimated_amount',
        label: 'Estimated Amount',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createActionsColumn<PurchaseRequest>(),
];
