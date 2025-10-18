'use client';

import * as React from 'react';
import { GenericDataTable } from '@/components/common/DataTableCore';
import { employeeColumns } from './EmployeeColumns';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Employee } from '@/types';
import { DEPARTMENTS, POSITIONS } from '@/constants';

export function EmployeeDataTable({
    data,
    onAddEmployee,
    onEditEmployee,
    onDeleteEmployee,
    onViewEmployee,
    pagination,
    onPageChange,
    onPageSizeChange,
    onSearchChange,
    isLoading,
    filterValue = '',
    filters,
    onFilterChange,
    onResetFilters,
}: {
    data: Employee[];
    onAddEmployee: () => void;
    onEditEmployee: (employee: Employee) => void;
    onDeleteEmployee: (employee: Employee) => void;
    onViewEmployee: (employee: Employee) => void;
    pagination: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
    onPageChange: (page: number) => void;
    onPageSizeChange: (per_page: number) => void;
    onSearchChange: (search: string) => void;
    isLoading?: boolean;
    filterValue?: string;
    filters?: Record<string, string | undefined>;
    onFilterChange: (filters: Record<string, string | undefined>) => void;
    onResetFilters: () => void;
}) {
    const filterFields = [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder="Search employees..." />,
        },
        {
            name: 'department',
            label: 'Department',
            component: (
                <Select>
                    <SelectTrigger className="border-border bg-background">
                        <SelectValue placeholder="Select a department" />
                    </SelectTrigger>
                    <SelectContent className="border-border bg-background text-foreground">
                        {DEPARTMENTS.map((dept) => (
                            <SelectItem key={dept} value={dept}>
                                {dept}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ),
        },
        {
            name: 'position',
            label: 'Position',
            component: (
                <Select>
                    <SelectTrigger className="border-border bg-background">
                        <SelectValue placeholder="Select a position" />
                    </SelectTrigger>
                    <SelectContent className="border-border bg-background text-foreground">
                        {POSITIONS.map((pos) => (
                            <SelectItem key={pos} value={pos}>
                                {pos}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ),
        },
    ];

    return (
        <GenericDataTable
            columns={employeeColumns}
            data={data}
            pagination={pagination}
            onPageChange={onPageChange}
            onPageSizeChange={onPageSizeChange}
            onSearchChange={onSearchChange}
            isLoading={isLoading}
            filterValue={filterValue}
            filters={filters}
            onFilterChange={onFilterChange}
            onResetFilters={onResetFilters}
            exportEndpoint="/api/employees/export"
            filterFields={filterFields}
            onAdd={onAddEmployee}
            onEdit={onEditEmployee}
            onDelete={onDeleteEmployee}
            onView={onViewEmployee}
        />
    );
}
