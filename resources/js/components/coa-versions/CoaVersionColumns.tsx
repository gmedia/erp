'use client';

import { type CoaVersion } from '@/types/coa-version';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

export const coaVersionColumns: ColumnDef<CoaVersion>[] = [
    createSelectColumn<CoaVersion>(),
    createTextColumn<CoaVersion>({
        accessorKey: 'name',
        label: { key: 'column.name', fallback: 'Name' },
        primary: true,
    }),
    {
        id: 'fiscal_year.name',
        accessorKey: 'fiscal_year.name',
        ...createSortingHeader({
            key: 'column.fiscal_year',
            fallback: 'Fiscal Year',
        }),
        cell: (info) => info.row.original.fiscal_year?.name || '-',
    },
    createStatusBadgeColumn<CoaVersion>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    createDateColumn<CoaVersion>({
        accessorKey: 'created_at',
        label: { key: 'column.created_at', fallback: 'Created At' },
    }),
    createActionsColumn<CoaVersion>(),
];
