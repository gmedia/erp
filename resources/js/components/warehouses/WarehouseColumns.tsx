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
    createTextColumn<Warehouse>({ accessorKey: 'code', label: 'Code' }),
    createTextColumn<Warehouse>({
        accessorKey: 'name',
        label: 'Name',
        primary: true,
    }),
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    createActionsColumn<Warehouse>(),
];
