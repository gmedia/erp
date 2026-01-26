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

import { Supplier } from '@/types/entity';
import { Badge } from '@/components/ui/badge';

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
        <Badge variant={status === 'active' ? 'default' : 'secondary'}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </Badge>
    );
};

export const supplierColumns: ColumnDef<Supplier>[] = [
    createSelectColumn<Supplier>(),
    createTextColumn<Supplier>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Supplier>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Supplier>({ accessorKey: 'phone', label: 'Phone' }),
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'category_id',
        ...createSortingHeader('Category'),
        cell: renderCategoryCell,
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createActionsColumn<Supplier>(),
];
