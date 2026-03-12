import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { createSortingHeader } from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';
import { Eye } from 'lucide-react';

export interface ApprovalAuditTrailItem {
    id: number;
    approval_request_id: number | null;
    approvable_type: string;
    approvable_type_short: string;
    approvable_id: number;
    event: string;
    actor_user_id: number | null;
    actor_user_name: string;
    step_order: number | null;
    metadata: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

interface ColumnsProps {
    onViewDetail: (item: ApprovalAuditTrailItem) => void;
}

export const createApprovalAuditTrailColumns = ({
    onViewDetail,
}: ColumnsProps): ColumnDef<ApprovalAuditTrailItem>[] => [
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Date'),
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            return <div>{date ? new Date(date).toLocaleString() : '-'}</div>;
        },
    },
    {
        accessorKey: 'approvable_type',
        ...createSortingHeader('Document Type'),
        cell: ({ row }) => <div>{row.original.approvable_type_short}</div>,
    },
    {
        accessorKey: 'approvable_id',
        ...createSortingHeader('Document ID'),
        cell: ({ row }) => <div>{row.getValue('approvable_id')}</div>,
    },
    {
        accessorKey: 'event',
        ...createSortingHeader('Event'),
        cell: ({ row }) => {
            const eventStr = row.getValue('event') as string;
            const formatted = eventStr
                ? eventStr
                      .replaceAll('_', ' ')
                      .replaceAll(/\b\w/g, (letter) =>
                          letter.toUpperCase(),
                      )
                : '-';
            let variant: 'default' | 'secondary' | 'destructive' | 'outline' =
                'outline';

            if (eventStr.includes('approved')) variant = 'default';
            if (eventStr === 'step_rejected') variant = 'destructive';
            if (eventStr === 'submitted') variant = 'secondary';
            if (eventStr === 'auto_approved') variant = 'default';
            if (eventStr === 'delegated' || eventStr === 'escalated')
                variant = 'secondary';

            return <Badge variant={variant}>{formatted}</Badge>;
        },
    },
    {
        accessorKey: 'actor_user_id',
        ...createSortingHeader('Actor'),
        cell: ({ row }) => <div>{row.original.actor_user_name}</div>,
    },
    {
        accessorKey: 'step_order',
        header: 'Step',
        cell: ({ row }) => <div>{row.getValue('step_order') || '-'}</div>,
        enableSorting: false,
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const item = row.original;
            return (
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onViewDetail(item)}
                    title="View Details"
                >
                    <Eye className="h-4 w-4" />
                </Button>
            );
        },
    },
];
