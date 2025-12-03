'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { PositionForm } from '@/components/positions/PositionForm';
import positions from '@/routes/positions';
import { Position, PositionFormData } from '@/types/position';
import { type BreadcrumbItem } from '@/types';
import { positionColumns } from '@/components/positions/PositionColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Positions',
        href: positions.index().url,
    },
];

export default function PositionIndex() {
    return (
        <CrudPage<Position, PositionFormData>
            config={{
                entityName: 'Position',
                entityNamePlural: 'Positions',
                apiEndpoint: '/api/positions',
                queryKey: ['positions'],
                breadcrumbs,
                DataTableComponent: GenericDataTable,
                FormComponent: PositionForm,
                mapDataTableProps: (props) => ({
                    data: props.data,
                    onAdd: props.onAdd,
                    onEdit: props.onEdit,
                    onDelete: props.onDelete,
                    pagination: props.pagination,
                    onPageChange: props.onPageChange,
                    onPageSizeChange: props.onPageSizeChange,
                    onSearchChange: props.onSearchChange,
                    isLoading: props.isLoading,
                    filterValue: props.filterValue,
                    filters: props.filters,
                    onFilterChange: props.onFilterChange,
                    onResetFilters: props.onResetFilters,
                    columns: positionColumns,
                    exportEndpoint: '/api/positions/export',
                    entityType: 'position',
                }),
                mapFormProps: (props) => ({
                    open: props.open,
                    onOpenChange: props.onOpenChange,
                    position: props.item,
                    onSubmit: props.onSubmit,
                    isLoading: props.isLoading,
                }),
                getDeleteMessage: (position) => 
                    `This action cannot be undone. This will permanently delete ${position.name}'s position record.`,
            }}
        />
    );
}