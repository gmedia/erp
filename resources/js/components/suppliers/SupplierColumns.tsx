'use client';

import { ColumnDef } from '@tanstack/react-table';

import {
    createActionsColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { Supplier } from '@/types/entity';

/**
 * Cell renderer for branch column
 */
const renderBranchCell = ({ row }: { row: { original: Supplier } }) => {
    const val = row.original.branch;
    return <div>{val ? val.name : '-'}</div>;
};

/**
 * Cell renderer for category column
 */
const renderCategoryCell = ({ row }: { row: { original: Supplier } }) => {
    const category = row.original.category;
    return <Badge variant="outline">{category?.name || '-'}</Badge>;
};

/**
 * Cell renderer for status column
 */
const renderStatusCell = ({ row }: { row: { original: Supplier } }) => {
    const status = row.original.status;
    return (
        <Badge variant={statusBadgeVariant(status)}>
            {formatStatusLabel(status)}
        </Badge>
    );
};

export const supplierColumns: ColumnDef<Supplier>[] = [
    createSelectColumn<Supplier>(),
    createTextColumn<Supplier>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    createEmailColumn<Supplier>({
        accessorKey: 'email',
        label: { key: 'column.email', fallback: 'Email' },
    }),
    createPhoneColumn<Supplier>({
        accessorKey: 'phone',
        label: { key: 'column.phone', fallback: 'Phone' },
    }),
    {
        accessorKey: 'branch',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'category',
        ...createSortingHeader({
            key: 'column.category',
            fallback: 'Category',
        }),
        cell: renderCategoryCell,
    },
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: renderStatusCell,
    },
    createActionsColumn<Supplier>(),
];
