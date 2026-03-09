'use client';

import { Badge } from '@/components/ui/badge';
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
    return <Badge variant="outline">{branch?.name || '-'}</Badge>;
};

export const warehouseColumns: ColumnDef<Warehouse>[] = [
    createSelectColumn<Warehouse>(),
    createTextColumn<Warehouse>({ accessorKey: 'code', label: 'Code' }),
    createTextColumn<Warehouse>({ accessorKey: 'name', label: 'Name' }),
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    createActionsColumn<Warehouse>(),
];
