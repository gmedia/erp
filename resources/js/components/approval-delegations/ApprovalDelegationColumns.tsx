import { Badge } from '@/components/ui/badge';
import {
    formatClassName,
    formatStatusLabel,
    statusBadgeVariant,
} from '@/lib/status-badge';
import { ApprovalDelegation } from '@/types/approval-delegation';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { ColumnDef } from '@tanstack/react-table';

const formatDate = (dateString?: string | null) => {
    return formatDateByRegionalSettings(dateString);
};

export const approvalDelegationColumns: ColumnDef<ApprovalDelegation>[] = [
    createSelectColumn<ApprovalDelegation>(),
    {
        accessorKey: 'delegator.name',
        id: 'delegator_user_id',
        ...createSortingHeader('Delegator'),
        cell: ({ row }) => {
            return <span>{row.original.delegator?.name}</span>;
        },
    },
    {
        accessorKey: 'delegate.name',
        id: 'delegate_user_id',
        ...createSortingHeader('Delegate'),
        cell: ({ row }) => {
            return <span>{row.original.delegate?.name}</span>;
        },
    },
    {
        accessorKey: 'approvable_type',
        ...createSortingHeader('Approvable Type'),
        cell: ({ row }) => (
            <span>
                {row.original.approvable_type
                    ? formatClassName(row.original.approvable_type)
                    : 'All types'}
            </span>
        ),
    },
    {
        accessorKey: 'start_date',
        ...createSortingHeader('Start Date'),
        cell: ({ row }) => {
            return <span>{formatDate(row.original.start_date)}</span>;
        },
    },
    {
        accessorKey: 'end_date',
        ...createSortingHeader('End Date'),
        cell: ({ row }) => {
            return <span>{formatDate(row.original.end_date)}</span>;
        },
    },
    {
        accessorKey: 'reason',
        ...createSortingHeader('Reason'),
        cell: ({ row }) => {
            return (
                <span className="block max-w-[200px] truncate">
                    {row.original.reason || '-'}
                </span>
            );
        },
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            // Status turunan tanggal dari API (W5-17).
            const status =
                row.original.status ??
                (row.original.is_active ? 'active' : 'inactive');
            return (
                <Badge variant={statusBadgeVariant(status)}>
                    {formatStatusLabel(status)}
                </Badge>
            );
        },
    },
    createActionsColumn<ApprovalDelegation>(),
];
