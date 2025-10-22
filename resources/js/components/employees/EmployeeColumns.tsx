'use client';

import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
} from '@/components/common/BaseColumns';
import { formatDate } from '@/lib/utils';
import { Employee } from '@/types/employee';
import { ColumnDef } from '@tanstack/react-table';

export function getEmployeeColumns(options: {
    onEdit?: (employee: Employee) => void;
    onDelete?: (employee: Employee) => void;
    onView?: (employee: Employee) => void;
}) {
    const { onEdit, onDelete, onView } = options;
    const columns: ColumnDef<Employee>[] = [
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
            ...createSortingHeader<Employee>('Position'),
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
                onView,
                onEdit: onEdit ? (item) => onEdit(item) : () => {},
                onDelete: onDelete ? (item) => onDelete(item) : () => {},
            }),
        },
    ];
    return columns;
}
export const employeeColumns: ColumnDef<Employee>[] = getEmployeeColumns({});
