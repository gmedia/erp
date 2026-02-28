import { Badge } from '@/components/ui/badge';
import { type ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { createSortingHeader, createSelectColumn, createActionsColumn } from '@/utils/columns';

import { type ApprovalFlow } from '@/types/entity';

export const approvalFlowColumns: ColumnDef<ApprovalFlow>[] = [
    createSelectColumn<ApprovalFlow>(),
    {
        accessorKey: 'code',
        ...createSortingHeader('Code'),
    },
    {
        accessorKey: 'name',
        ...createSortingHeader('Name'),
    },
    {
        accessorKey: 'approvable_type',
        ...createSortingHeader('Approvable Type'),
        cell: ({ row }) => {
            const type = row.getValue('approvable_type') as string;
            if (!type) return '';
            const parts = type.split('\\');
            return parts[parts.length - 1];
        },
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return (
                <Badge variant={isActive ? 'default' : 'secondary'}>
                    {isActive ? 'Active' : 'Inactive'}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Created At'),
        cell: ({ row }) => {
            const dateStr = row.getValue('created_at') as string;
            return dateStr ? format(new Date(dateStr), 'dd MMM yyyy HH:mm') : '';
        },
    },
    createActionsColumn<ApprovalFlow>(),
];
