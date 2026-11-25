'use client';

import { ColumnDef } from '@tanstack/react-table';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

import { type Warehouse } from '@/types/entity';

const renderBranchCell = ({ row }: { row: { original: Warehouse } }) => {
    const branch = row.original.branch;
    return <span>{branch?.name || '-'}</span>;
};

export const warehouseColumns: ColumnDef<Warehouse>[] = [
    createSelectColumn<Warehouse>(),
    createTextColumn<Warehouse>({
        accessorKey: 'code',
        label: { key: 'column.code', fallback: 'Code' },
    }),
    createTextColumn<Warehouse>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    {
        accessorKey: 'branch',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    createActionsColumn<Warehouse>(),
];
