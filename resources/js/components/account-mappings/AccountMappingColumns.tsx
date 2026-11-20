'use client';

import { type AccountMapping } from '@/types/account-mapping';
import {
    createActionsColumn,
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

function formatAccountLabel(
    account?: {
        coa_version?: { name: string } | null;
        code: string;
        name: string;
    } | null,
): string {
    if (!account) return '-';
    const version = account.coa_version?.name;
    const base = `${account.code} - ${account.name}`;
    return version ? `${version} • ${base}` : base;
}

export const accountMappingColumns: ColumnDef<AccountMapping>[] = [
    createSelectColumn<AccountMapping>(),
    {
        id: 'source',
        accessorKey: 'source_account_id', // Add accessorKey to satisfy sorting requirements if needed
        enableSorting: true,
        ...createSortingHeader('Source Account'),
        cell: ({ row }) => formatAccountLabel(row.original.source_account),
    },
    {
        id: 'target',
        accessorKey: 'target_account_id', // Add accessorKey to satisfy sorting requirements if needed
        enableSorting: true,
        ...createSortingHeader('Target Account'),
        cell: ({ row }) => formatAccountLabel(row.original.target_account),
    },
    createBadgeColumn<AccountMapping>({
        accessorKey: 'type',
        label: 'Type',
        colorMap: {
            rename: 'bg-muted text-foreground',
            merge: 'bg-info/10 text-info',
            split: 'bg-chart-3/10 text-chart-3',
        },
    }),
    createTextColumn<AccountMapping>({ accessorKey: 'notes', label: 'Notes' }),
    createDateColumn<AccountMapping>({
        accessorKey: 'created_at',
        label: 'Created At',
    }),
    createActionsColumn<AccountMapping>(),
];
