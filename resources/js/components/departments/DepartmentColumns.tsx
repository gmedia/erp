'use client';

import { ArrowUpDown, MoreHorizontal } from 'lucide-react';
import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { createSelectColumn } from '@/components/common/SelectColumn';
import { GenericActions } from '@/components/common/ActionsDropdown';
import { Department } from '@/types/department';
import { formatDate } from '@/lib/utils';

export const departmentColumns: ColumnDef<Department>[] = [
    createSelectColumn<Department>(),
    {
        accessorKey: 'name',
        header: ({ column }) => (
            <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                Name
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
    },
    {
        accessorKey: 'created_at',
        header: ({ column }) => (
            <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                Created At
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('created_at'))}</div>;
        },
    },
    {
        accessorKey: 'updated_at',
        header: ({ column }) => (
            <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                Updated At
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('updated_at'))}</div>;
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const department = row.original;
            return (
                <GenericActions
                    item={department}
                    onEdit={(item) => console.log('Edit', item.id)}
                    onDelete={(item) => console.log('Delete', item.id)}
                />
            );
        },
    },
];
