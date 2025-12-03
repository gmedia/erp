'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { DepartmentForm } from '@/components/departments/DepartmentForm';
import { departments } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Department, DepartmentFormData } from '@/types/department';
import { departmentColumns } from '@/components/departments/DepartmentColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments().url,
    },
];

export default function DepartmentIndex() {
    return (
        <CrudPage<Department, DepartmentFormData>
            config={{
                entityName: 'Department',
                entityNamePlural: 'Departments',
                apiEndpoint: '/api/departments',
                queryKey: ['departments'],
                breadcrumbs,
                
                DataTableComponent: GenericDataTable,
                FormComponent: DepartmentForm,
                
                // Map the generic props to component-specific props
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
                    columns: departmentColumns,
                    exportEndpoint: '/api/departments/export',
                    entityType: 'department',
                }),
                
                mapFormProps: (props) => ({
                    open: props.open,
                    onOpenChange: props.onOpenChange,
                    department: props.item,
                    onSubmit: props.onSubmit,
                    isLoading: props.isLoading,
                }),
            }}
        />
    );
}