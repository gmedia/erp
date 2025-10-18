'use client';

import { ColumnDef } from '@tanstack/react-table';
import { createSelectColumn, createSortingHeader, createActionsColumn } from '@/components/common/BaseColumns';
import { formatDate } from '@/lib/utils';
import { Employee } from '@/types/employee';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    {
        accessorKey: 'name',
        ...createSortingHeader<Employee>('Name'),
    },
    {
        accessorKey: 'email',
        ...createSortingHeader<Employee>('Email'),
    },
    {
        accessorKey: 'phone',
        header: 'Phone',
    },
    {
        accessorKey: 'department',
        ...createSortingHeader<Employee>('Department'),
    },
    {
        accessorKey: 'position',
        header: 'Position',
    },
    {
        accessorKey: 'salary',
        ...createSortingHeader<Employee>('Salary'),
        cell: ({ row }) => {
            const salary = parseFloat(row.getValue('salary'));
            const formatted = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(salary);
            return <div className="font-medium">{formatted}</div>;
        },
    },
    {
        accessorKey: 'hire_date',
        ...createSortingHeader<Employee>('Hire Date'),
        cell: ({ row }) => {
            return <div>{formatDate(row.getValue('hire_date'))}</div>;
        },
    },
    {
        ...createActionsColumn<Employee>({
            onView: (item) => navigator.clipboard.writeText(item.email),
            onEdit: (item) => console.log('Edit', item.id),
            onDelete: (item) => console.log('Delete', item.id),
        }),
    },
];
