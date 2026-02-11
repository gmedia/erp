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

/**
 * Cell renderer for department column - handles both object and string values
 */
const renderDepartmentCell = ({ row }: { row: { original: Employee } }) => {
    const val = row.original.department;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

/**
 * Cell renderer for position column - handles both object and string values
 */
const renderPositionCell = ({ row }: { row: { original: Employee } }) => {
    const val = row.original.position;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

/**
 * Cell renderer for branch column - handles both object and string values
 */
const renderBranchCell = ({ row }: { row: { original: Employee } }) => {
    const val = row.original.branch;
    return <div>{typeof val === 'object' ? val.name : val}</div>;
};

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    {
        id: 'department_id',
        accessorKey: 'department',
        ...createSortingHeader('Department'),
        cell: renderDepartmentCell,
    },
    {
        id: 'position_id',
        accessorKey: 'position',
        ...createSortingHeader('Position'),
        cell: renderPositionCell,
    },
    {
        id: 'branch_id',
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    createCurrencyColumn<Employee>({
        accessorKey: 'salary',
        label: 'Salary',
        currency: 'IDR',
        locale: 'id-ID',
    }),
    createDateColumn<Employee>({
        accessorKey: 'hire_date',
        label: 'Hire Date',
    }),
    createActionsColumn<Employee>(),
];
