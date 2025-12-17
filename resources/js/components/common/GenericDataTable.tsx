'use client';

import { createActionsColumn } from '@/utils/columns';
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
    entityType: 'department' | 'position' | 'employee';
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
    entityType,
}: GenericDataTableProps<T>) {
    const getFilterFields = () => {
        const baseFields = [
            {
                name: 'search',
                label: 'Search',
                component: <Input placeholder={`Search ${entityType}s...`} />,
            },
        ];

        if (entityType === 'employee') {
            return [
                ...baseFields,
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

        return baseFields;
    };

    const processedColumns = React.useMemo(() => {
        return columns.map((col) => {
            if (col.id === 'actions') {
                return {
                    ...col,
                    ...createActionsColumn<T>({
                        onView,
                        onEdit: (item) => onEdit(item),
                        onDelete: (item) => onDelete(item),
                    }),
                };
            }
            return col;
        });
    }, [columns, onEdit, onDelete, onView]);

    const filterFields = getFilterFields();

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
            filterFields={filterFields}
            onAdd={onAdd}
            onEdit={onEdit}
            onDelete={onDelete}
            onView={onView}
        />
    );
}
