'use client';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/components/common/BaseColumns';
import { formatDate } from '@/lib/utils';
import { Department } from '@/types/department';
import { ColumnDef } from '@tanstack/react-table';

export const departmentColumns: ColumnDef<Department>[] = [
    createSelectColumn<Department>(),
    {
        accessorKey: 'name',
        ...createSortingHeader<Department>('Name'),
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader<Department>('Created At'),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('created_at'))}</div>;
        },
    },
    {
        accessorKey: 'updated_at',
        ...createSortingHeader<Department>('Updated At'),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('updated_at'))}</div>;
        },
    },
    {
        ...createActionsColumn<Department>({
            onEdit: () => {},
            onDelete: () => {},
        }),
    },
];