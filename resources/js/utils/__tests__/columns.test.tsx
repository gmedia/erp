// Type checking test for column utilities
import { Department } from '@/types/department';
import { Employee } from '@/types/employee';
import { Position } from '@/types/position';
import {
    createActionsColumn,
    createCurrencyColumn,
    createDateColumn,
    createEmailColumn,
    createPhoneColumn,
    createSelectColumn,
    createTextColumn,
} from '../columns';

// Test type safety with Department
const departmentColumns = [
    createSelectColumn<Department>(),
    createTextColumn<Department>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Department>({
        accessorKey: 'created_at',
        label: 'Created At',
    }),
    createActionsColumn<Department>({
        onEdit: (dept) => console.log('Edit', dept),
        onDelete: (dept) => console.log('Delete', dept),
    }),
];

// Test type safety with Employee
const employeeColumns = [
    createSelectColumn<Employee>(),
    createTextColumn<Employee>({ accessorKey: 'name', label: 'Name' }),
    createEmailColumn<Employee>({ accessorKey: 'email', label: 'Email' }),
    createPhoneColumn<Employee>({ accessorKey: 'phone', label: 'Phone' }),
    createCurrencyColumn<Employee>({ accessorKey: 'salary', label: 'Salary' }),
    createDateColumn<Employee>({
        accessorKey: 'hire_date',
        label: 'Hire Date',
    }),
    createActionsColumn<Employee>({
        onEdit: (emp) => console.log('Edit', emp),
        onDelete: (emp) => console.log('Delete', emp),
        onView: (emp) => console.log('View', emp),
    }),
];

// Test type safety with Position
const positionColumns = [
    createSelectColumn<Position>(),
    createTextColumn<Position>({ accessorKey: 'name', label: 'Name' }),
    createDateColumn<Position>({
        accessorKey: 'updated_at',
        label: 'Updated At',
        enableSorting: false,
    }),
    createActionsColumn<Position>({
        onEdit: (pos) => console.log('Edit', pos),
        onDelete: (pos) => console.log('Delete', pos),
    }),
];

// Test with various options
const testColumns = [
    createTextColumn<Employee>({
        accessorKey: 'name',
        label: 'Full Name',
        className: 'font-bold',
    }),
    createCurrencyColumn<Employee>({
        accessorKey: 'salary',
        label: 'Annual Salary',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }),
    createDateColumn<Employee>({
        accessorKey: 'hire_date',
        label: 'Start Date',
    }),
];

// This file is for type checking only - it should compile without errors
console.log('Type checking passed for column utilities');
