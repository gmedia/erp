'use client';

import { Badge } from '@/components/ui/badge';
import { statusBadgeVariant } from '@/lib/status-badge';
import { Employee } from '@/types/entity';
import {
    createActionsColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const formatCurrency = (value: string | null | undefined): string => {
    if (!value) {
        return '-';
    }

    const amount = Number(value);
    if (Number.isNaN(amount)) {
        return value;
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('id-ID');
};

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    {
        id: 'employee_id',
        accessorKey: 'employee_id',
        ...createSortingHeader('NIK'),
        cell: ({ row }) => <div>{row.original.employee_id}</div>,
    },
    createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
    {
        id: 'employments.employment_status',
        accessorFn: (row) => row.current_employment?.employment_status,
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.original.current_employment?.employment_status;
            if (!status) {
                return <div>-</div>;
            }

            const label = status === 'intern' ? 'Intern' : 'Regular';
            return (
                <Badge variant={statusBadgeVariant(status)}>{label}</Badge>
            );
        },
    },
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    {
        id: 'employments.department_id',
        accessorFn: (row) => row.current_employment?.department?.name,
        ...createSortingHeader('Department'),
        cell: ({ row }) => (
            <div>
                {row.original.current_employment?.department?.name ?? '-'}
            </div>
        ),
    },
    {
        id: 'employments.position_id',
        accessorFn: (row) => row.current_employment?.position?.name,
        ...createSortingHeader('Position'),
        cell: ({ row }) => (
            <div>{row.original.current_employment?.position?.name ?? '-'}</div>
        ),
    },
    {
        id: 'employments.branch_id',
        accessorFn: (row) => row.current_employment?.branch?.name,
        ...createSortingHeader('Branch'),
        cell: ({ row }) => (
            <div>{row.original.current_employment?.branch?.name ?? '-'}</div>
        ),
    },
    {
        id: 'employments.salary',
        accessorFn: (row) => row.current_employment?.salary,
        ...createSortingHeader('Salary'),
        cell: ({ row }) => (
            <div>{formatCurrency(row.original.current_employment?.salary)}</div>
        ),
    },
    {
        id: 'employments.hire_date',
        accessorFn: (row) => row.current_employment?.hire_date,
        ...createSortingHeader('Hire Date'),
        cell: ({ row }) => (
            <div>{formatDate(row.original.current_employment?.hire_date)}</div>
        ),
    },
    createActionsColumn<Employee>(),
];
