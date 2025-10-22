'use client';

import { createActionsColumn } from '@/components/common/BaseColumns';
import { GenericDataTable } from '@/components/common/DataTableCore';
import { Input } from '@/components/ui/input';
import { Position } from '@/types/position';
import * as React from 'react';
import { positionColumns } from './PositionColumns';

export function PositionDataTable({
    data,
    onAddPosition,
    onEditPosition,
    onDeletePosition,
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
    data: Position[];
    onAddPosition: () => void;
    onEditPosition: (position: Position) => void;
    onDeletePosition: (position: Position) => void;
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
            component: <Input placeholder="Search positions..." />,
        },
    ];

    const columns = React.useMemo(() => {
        return positionColumns.map((col) => {
            if (col.id === 'actions') {
                return {
                    ...col,
                    ...createActionsColumn<Position>({
                        onEdit: (item) => onEditPosition(item),
                        onDelete: (item) => onDeletePosition(item),
                    }),
                };
            }
            return col;
        });
    }, [onEditPosition, onDeletePosition]);

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
            exportEndpoint="/api/positions/export"
            filterFields={filterFields}
            onAdd={onAddPosition}
            onEdit={onEditPosition}
            onDelete={onDeletePosition}
        />
    );
}
