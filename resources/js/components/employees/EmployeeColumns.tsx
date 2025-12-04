'use client';

import {
    createActionsColumn,
    createSelectColumn,
} from '@/components/common/BaseColumns';
import {
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createSimpleTextColumn,
    createTextColumn,
} from '@/components/common/ColumnUtils';
import { Employee } from '@/types/employee';
import { ColumnDef } from '@tanstack/react-table';

export const employeeColumns: ColumnDef<Employee>[] = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>('name', 'Name'),
    createEmailColumn<Employee>('email', 'Email'),
    createPhoneColumn<Employee>('phone', 'Phone'),
    createTextColumn<Employee>('department', 'Department'),
    createTextColumn<Employee>('position', 'Position'),
    createCurrencyColumn<Employee>('salary', 'Salary'),
    createDateColumn<Employee>('hire_date', 'Hire Date'),
    {
        ...createActionsColumn<Employee>({
            onEdit: () => {},
            onDelete: () => {},
        }),
    },
];

// Backward compatibility: keep getEmployeeColumns for existing usage
export function getEmployeeColumns(options: {
    onEdit?: (employee: Employee) => void;
    onDelete?: (employee: Employee) => void;
    onView?: (employee: Employee) => void;
}) {
    const { onEdit, onDelete, onView } = options;
    
    // Create a new columns array with the provided callbacks
    const columns: ColumnDef<Employee>[] = [
        createSelectColumn<Employee>(),
        createTextColumn<Employee>('name', 'Name'),
        createEmailColumn<Employee>('email', 'Email'),
        createPhoneColumn<Employee>('phone', 'Phone'),
        createTextColumn<Employee>('department', 'Department'),
        createTextColumn<Employee>('position', 'Position'),
        createCurrencyColumn<Employee>('salary', 'Salary'),
        createDateColumn<Employee>('hire_date', 'Hire Date'),
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