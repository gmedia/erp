import { ColumnDef } from '@tanstack/react-table';
import { type Pipeline } from '@/types/entity';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { Badge } from '@/components/ui/badge';

const renderStatusCell = ({ row }: { row: { original: Pipeline } }) => {
    const is_active = row.original.is_active;
    return (
        <Badge variant={is_active ? 'default' : 'secondary'}>
            {is_active ? 'Active' : 'Inactive'}
        </Badge>
    );
};

export const pipelineColumns: ColumnDef<Pipeline>[] = [
    createSelectColumn<Pipeline>(),
    createTextColumn<Pipeline>({ accessorKey: 'name', label: 'Name' }),
    createTextColumn<Pipeline>({ accessorKey: 'code', label: 'Code' }),
    {
        accessorKey: 'entity_type',
        ...createSortingHeader('Entity'),
        cell: ({ row }: { row: { original: Pipeline } }) => {
            const parts = row.original.entity_type.split('\\');
            return parts[parts.length - 1];
        }
    },
    {
        accessorKey: 'version',
        ...createSortingHeader('Version'),
    },
    {
        accessorKey: 'created_by',
        ...createSortingHeader('Creator'),
        cell: ({ row }: { row: { original: Pipeline } }) => row.original.created_by?.name || 'System',
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createActionsColumn<Pipeline>(),
];
