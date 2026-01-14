'use client';

import { ColumnDef } from '@tanstack/react-table';

import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';

import { Employee } from '@/types/entity';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    {
        accessorKey: 'department',
        ...createSortingHeader('Department'),
        cell: ({ row }) => {
            const val = row.original.department;
            return <div>{typeof val === 'object' ? val.name : val}</div>;
        },
    },
    {
        accessorKey: 'position',
        ...createSortingHeader('Position'),
        cell: ({ row }) => {
            const val = row.original.position;
            return <div>{typeof val === 'object' ? val.name : val}</div>;
        },
    },
    createCurrencyColumn<Employee>({ accessorKey: 'salary', label: 'Salary' }),
    createDateColumn<Employee>({
        accessorKey: 'hire_date',
        label: 'Hire Date',
    }),
    createActionsColumn<Employee>(),
];
