import { ColumnDef } from '@tanstack/react-table';
import { createSortingHeader } from '@/utils/columns';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Eye } from 'lucide-react';

export interface PipelineAuditTrailItem {
    id: number;
    pipeline_entity_state_id: number;
    entity_type: string;
    entity_type_short: string;
    entity_id: string;
    pipeline_name: string | null;
    from_state_id: number | null;
    from_state_name: string | null;
    from_state_color: string | null;
    to_state_id: number | null;
    to_state_name: string | null;
    to_state_color: string | null;
    transition_id: number | null;
    transition_name: string | null;
    performed_by: number | null;
    performed_by_name: string;
    comment: string | null;
    metadata: any;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

interface ColumnsProps {
    onViewDetail: (item: PipelineAuditTrailItem) => void;
}

export const createPipelineAuditTrailColumns = ({ onViewDetail }: ColumnsProps): ColumnDef<PipelineAuditTrailItem>[] => [
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Date'),
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            return <div>{date ? new Date(date).toLocaleString() : '-'}</div>;
        },
    },
    {
        accessorKey: 'pipeline_name',
        header: 'Pipeline',
        cell: ({ row }) => <div className="font-medium">{row.getValue('pipeline_name') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'entity_type',
        ...createSortingHeader('Entity Type'),
        cell: ({ row }) => <div>{row.original.entity_type_short}</div>,
    },
    {
        accessorKey: 'entity_id',
        header: 'Entity ID',
        cell: ({ row }) => <div>{row.getValue('entity_id')}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'from_state_name',
        header: 'From State',
        cell: ({ row }) => {
            const name = row.getValue('from_state_name') as string | null;
            const color = row.original.from_state_color;
            if (!name) return <Badge variant="outline">Initial</Badge>;
            return (
                <Badge
                    style={color ? { backgroundColor: color, color: '#fff', borderColor: color } : undefined}
                    variant="outline"
                >
                    {name}
                </Badge>
            );
        },
        enableSorting: false,
    },
    {
        accessorKey: 'to_state_name',
        header: 'To State',
        cell: ({ row }) => {
            const name = row.getValue('to_state_name') as string | null;
            const color = row.original.to_state_color;
            if (!name) return '-';
            return (
                <Badge
                    style={color ? { backgroundColor: color, color: '#fff', borderColor: color } : undefined}
                    variant="outline"
                >
                    {name}
                </Badge>
            );
        },
        enableSorting: false,
    },
    {
        accessorKey: 'transition_name',
        header: 'Transition',
        cell: ({ row }) => <div>{row.getValue('transition_name') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'performed_by',
        ...createSortingHeader('Performed By'),
        cell: ({ row }) => <div>{row.original.performed_by_name}</div>,
    },
    {
        accessorKey: 'comment',
        header: 'Comment',
        cell: ({ row }) => {
            const comment = row.getValue('comment') as string | null;
            if (!comment) return '-';
            return (
                <div className="max-w-[200px] truncate" title={comment}>
                    {comment}
                </div>
            );
        },
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
