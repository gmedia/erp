import { Badge } from '@/components/ui/badge';
import { formatClassName, statusBadgeVariant } from '@/lib/status-badge';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

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
        cell: ({ row }) => (
            <span>
                {formatClassName(row.getValue('approvable_type') as string)}
            </span>
        ),
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return (
                <Badge
                    variant={statusBadgeVariant(
                        isActive ? 'active' : 'inactive',
                    )}
                >
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
            return formatDateTimeByRegionalSettings(dateStr, {
                fallback: '',
            });
        },
    },
    createActionsColumn<ApprovalFlow>(),
];
