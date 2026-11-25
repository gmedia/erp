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
import { Customer } from '@/types/entity';

/**
 * Cell renderer for branch column - handles both object and string values
 */
const renderBranchCell = ({ row }: { row: { original: Customer } }) => {
    const val = row.original.branch;
    return <div>{typeof val === 'object' ? val?.name : val}</div>;
};

/**
 * Cell renderer for category column - handles both object and string values
 */
const renderCategoryCell = ({ row }: { row: { original: Customer } }) => {
    const val = row.original.category;
    return <div>{typeof val === 'object' ? val?.name : val}</div>;
};

/**
 * Cell renderer for status column - displays as badge
 */
const renderStatusCell = ({ row }: { row: { original: Customer } }) => {
    const status = row.original.status;
    return (
        <Badge variant={statusBadgeVariant(status)}>
            {formatStatusLabel(status)}
        </Badge>
    );
};

export const customerColumns: ColumnDef<Customer>[] = [
    createSelectColumn<Customer>(),
    createTextColumn<Customer>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
        description: (row) =>
            typeof row.category === 'object'
                ? (row.category?.name ?? null)
                : (row.category ?? null),
    }),
    createEmailColumn<Customer>({
        accessorKey: 'email',
        label: { key: 'column.email', fallback: 'Email' },
    }),
    createPhoneColumn<Customer>({
        accessorKey: 'phone',
        label: { key: 'column.phone', fallback: 'Phone' },
    }),
    {
        accessorKey: 'branch_id',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'category_id',
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
    createActionsColumn<Customer>(),
];
