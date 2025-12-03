'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { PositionDataTable } from '@/components/positions/PositionDataTable';
import { PositionForm } from '@/components/positions/PositionForm';
import positions from '@/routes/positions';
import { Position, PositionFormData } from '@/types/position';
import { type BreadcrumbItem } from '@/types';

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
                DataTableComponent: PositionDataTable,
                FormComponent: PositionForm,
                mapDataTableProps: (props) => ({
                    data: props.data,
                    onAddPosition: props.onAdd,
                    onEditPosition: props.onEdit,
                    onDeletePosition: props.onDelete,
                    pagination: props.pagination,
                    onPageChange: props.onPageChange,
                    onPageSizeChange: props.onPageSizeChange,
                    onSearchChange: props.onSearchChange,
                    isLoading: props.isLoading,
                    filterValue: props.filterValue,
                    filters: props.filters,
                    onFilterChange: props.onFilterChange,
                    onResetFilters: props.onResetFilters,
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