'use client';

import { ColumnDef } from '@tanstack/react-table';

import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createTextColumn,
} from '@/utils/columns';

import { Employee } from '@/types/entity';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    createTextColumn<Employee>({
        accessorKey: 'department',
        label: 'Department',
    }),
    createTextColumn<Employee>({ accessorKey: 'position', label: 'Position' }),
    createCurrencyColumn<Employee>({ accessorKey: 'salary', label: 'Salary' }),
    createDateColumn<Employee>({
        accessorKey: 'hire_date',
        label: 'Hire Date',
    }),
    createActionsColumn<Employee>(),
];
