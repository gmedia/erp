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

import { Customer } from '@/types/entity';
import { Badge } from '@/components/ui/badge';

/**
 * Cell renderer for branch column - handles both object and string values
 */
const renderBranchCell = ({ row }: { row: { original: Customer } }) => {
    const val = row.original.branch;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

/**
 * Cell renderer for category column - handles both object and string values
 */
const renderCategoryCell = ({ row }: { row: { original: Customer } }) => {
    const val = row.original.category;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

/**
 * Cell renderer for status column - displays as badge
 */
const renderStatusCell = ({ row }: { row: { original: Customer } }) => {
    const status = row.original.status;
    return (
        <Badge variant={status === 'active' ? 'default' : 'destructive'}>
            {status === 'active' ? 'Active' : 'Inactive'}
        </Badge>
    );
};

export const customerColumns: ColumnDef<Customer>[] = [
    createSelectColumn<Customer>(),
    createTextColumn<Customer>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Customer>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Customer>({ accessorKey: 'phone', label: 'Phone' }),
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    {
        accessorKey: 'category',
        ...createSortingHeader('Category'),
        cell: renderCategoryCell,
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createActionsColumn<Customer>(),
];
