'use client';

import { ColumnDef } from '@tanstack/react-table';
import { createSelectColumn, createSortingHeader, createActionsColumn } from '@/components/common/BaseColumns';
import { formatDate } from '@/lib/utils';
import { Position } from '@/types/position';

export const positionColumns: ColumnDef<Position>[] = [
    createSelectColumn<Position>(),
    {
        accessorKey: 'name',
        ...createSortingHeader<Position>('Name'),
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader<Position>('Created At'),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('created_at'))}</div>;
        },
    },
    {
        accessorKey: 'updated_at',
        ...createSortingHeader<Position>('Updated At'),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('updated_at'))}</div>;
        },
    },
    {
        ...createActionsColumn<Position>({
            onEdit: (item) => console.log('Edit', item.id),
            onDelete: (item) => console.log('Delete', item.id),
        }),
    },
];
