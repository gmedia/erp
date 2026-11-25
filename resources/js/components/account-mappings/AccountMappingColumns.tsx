'use client';

import { type AccountMapping } from '@/types/account-mapping';
import {
    createActionsColumn,
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
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
        ...createSortingHeader({
            key: 'column.source_account',
            fallback: 'Source Account',
        }),
        cell: ({ row }) => formatAccountLabel(row.original.source_account),
    },
    {
        id: 'target',
        accessorKey: 'target_account_id', // Add accessorKey to satisfy sorting requirements if needed
        enableSorting: true,
        ...createSortingHeader({
            key: 'column.target_account',
            fallback: 'Target Account',
        }),
        cell: ({ row }) => formatAccountLabel(row.original.target_account),
    },
    createBadgeColumn<AccountMapping>({
        accessorKey: 'type',
        label: { key: 'column.type', fallback: 'Type' },
        colorMap: {
            rename: 'bg-muted text-foreground',
            merge: 'bg-info/10 text-info',
            split: 'bg-warning-soft text-warning',
        },
    }),
    {
        id: 'notes',
        accessorKey: 'notes',
        ...createSortingHeader({ key: 'column.notes', fallback: 'Notes' }),
        cell: ({ row }) => {
            const notes = row.original.notes ?? '-';
            return (
                <div className="max-w-64 truncate" title={notes}>
                    {notes}
                </div>
            );
        },
    },
    createDateColumn<AccountMapping>({
        accessorKey: 'created_at',
        label: { key: 'column.created_at', fallback: 'Created At' },
    }),
    createActionsColumn<AccountMapping>(),
];
