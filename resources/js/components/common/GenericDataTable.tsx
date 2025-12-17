'use client';


import { GenericDataTable as DataTableCore } from '@/components/common/DataTableCore';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { DEPARTMENTS, POSITIONS } from '@/constants';
import * as React from 'react';

interface PaginationInfo {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
}

type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

interface GenericDataTableProps<T extends Record<string, any>> {
    data: T[];
    onAdd: () => void;
    onEdit: (item: T) => void;
    onDelete: (item: T) => void;
    onView?: (item: T) => void;
    pagination: PaginationInfo;
    onPageChange: (page: number) => void;
    onPageSizeChange: (per_page: number) => void;
    onSearchChange: (search: string) => void;
    isLoading?: boolean;
    filterValue?: string;
    filters?: Record<string, string | undefined>;
    onFilterChange: (filters: Record<string, string | undefined>) => void;
    onResetFilters: () => void;
    columns: any[];
    exportEndpoint: string;
    filterFields?: FieldDescriptor[];
}

export function GenericDataTable<T extends Record<string, any>>({
    data,
    onAdd,
    onEdit,
    onDelete,
    onView,
    pagination,
    onPageChange,
    onPageSizeChange,
    onSearchChange,
    isLoading,
    filterValue = '',
    filters,
    onFilterChange,
    onResetFilters,
    columns,
    exportEndpoint,
    filterFields = [],
    entityName = '',
}: GenericDataTableProps<T> & { entityName?: string }) {
    // Add default search field if no filterFields provided
    const defaultFilterFields = filterFields.length === 0 ? [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder={`Search${entityName ? ` ${entityName.toLowerCase()}s` : ''}...`} />,
        },
    ] : filterFields;

    const processedColumns = columns;

    return (
        <DataTableCore
            columns={processedColumns}
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
            exportEndpoint={exportEndpoint}
            filterFields={defaultFilterFields}
            onAdd={onAdd}
            onEdit={onEdit}
            onDelete={onDelete}
            onView={onView}
        />
    );
}

// Convenience function for employee-specific filter fields
export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
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
                            <SelectItem key={dept.value} value={dept.value}>
                                {dept.label}
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
                            <SelectItem key={pos.value} value={pos.value}>
                                {pos.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ),
        },
    ];
}
