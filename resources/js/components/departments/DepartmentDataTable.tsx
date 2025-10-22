'use client';

import { createActionsColumn } from '@/components/common/BaseColumns';
import { GenericDataTable } from '@/components/common/DataTableCore';
import { Input } from '@/components/ui/input';
import { Department } from '@/types/department';
import * as React from 'react';
import { departmentColumns } from './DepartmentColumns';

export function DepartmentDataTable({
    data,
    onAddDepartment,
    onEditDepartment,
    onDeleteDepartment,
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
    data: Department[];
    onAddDepartment: () => void;
    onEditDepartment: (department: Department) => void;
    onDeleteDepartment: (department: Department) => void;
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
            component: <Input placeholder="Search departments..." />,
        },
    ];

    const columns = React.useMemo(() => {
        return departmentColumns.map((col) => {
            if (col.id === 'actions') {
                return {
                    ...col,
                    ...createActionsColumn<Department>({
                        onEdit: (item) => onEditDepartment(item),
                        onDelete: (item) => onDeleteDepartment(item),
                    }),
                };
            }
            return col;
        });
    }, [onEditDepartment, onDeleteDepartment]);

    return (
        <GenericDataTable
            columns={columns}
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
            exportEndpoint="/api/departments/export"
            filterFields={filterFields}
            onAdd={onAddDepartment}
            onEdit={onEditDepartment}
            onDelete={onDeleteDepartment}
        />
    );
}
