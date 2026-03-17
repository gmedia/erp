import { Badge } from '@/components/ui/badge';
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
        cell: ({ row }) => {
            const type = row.original.approvable_type;
            if (!type) return <span className="text-muted-foreground">-</span>;

            // Format App\Models\PurchaseOrder -> PurchaseOrder
            const parts = type.split('\\');
            return <span>{parts[parts.length - 1]}</span>;
        },
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
            const isActive = row.original.is_active;
            return (
                <Badge variant={isActive ? 'default' : 'secondary'}>
                    {isActive ? 'Active' : 'Inactive'}
                </Badge>
            );
        },
    },
    createActionsColumn<ApprovalDelegation>(),
];
