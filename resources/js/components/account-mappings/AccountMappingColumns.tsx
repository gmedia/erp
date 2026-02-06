'use client';

import { createActionsColumn, createBadgeColumn, createDateColumn } from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { type AccountMapping } from '@/types/account-mapping';

function formatAccountLabel(account?: {
    coa_version?: { name: string } | null;
    code: string;
    name: string;
} | null): string {
    if (!account) return '-';
    const version = account.coa_version?.name;
    const base = `${account.code} - ${account.name}`;
    return version ? `${version} • ${base}` : base;
}

export const accountMappingColumns: ColumnDef<AccountMapping>[] = [
    {
        id: 'source',
        header: 'Source Account',
        cell: ({ row }) => formatAccountLabel(row.original.source_account),
    },
    {
        id: 'target',
        header: 'Target Account',
        cell: ({ row }) => formatAccountLabel(row.original.target_account),
    },
    createBadgeColumn<AccountMapping>({
        accessorKey: 'type',
        label: 'Type',
        colorMap: {
            rename: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            merge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            split: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        },
    }),
    createDateColumn<AccountMapping>({ accessorKey: 'created_at', label: 'Created At' }),
    createActionsColumn<AccountMapping>(),
];
