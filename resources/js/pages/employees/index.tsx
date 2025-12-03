'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employees } from '@/routes';
import { Employee, EmployeeFormData } from '@/types/employee';
import { type BreadcrumbItem } from '@/types';
import { employeeColumns } from '@/components/employees/EmployeeColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: employees().url,
    },
];

export default function EmployeeIndex() {
    return (
        <CrudPage<Employee, EmployeeFormData, { search: string; department: string; position: string; sort_by?: string; sort_direction?: string }>
            config={{
                entityName: 'Employee',
                entityNamePlural: 'Employees',
                apiEndpoint: '/api/employees',
                queryKey: ['employees'],
                breadcrumbs,
                DataTableComponent: GenericDataTable,
                FormComponent: EmployeeForm,
                initialFilters: {
                    search: '',
                    department: '',
                    position: '',
                    sort_by: undefined,
                    sort_direction: undefined,
                },
                mapDataTableProps: (props) => ({
                    data: props.data,
                    onAdd: props.onAdd,
                    onEdit: props.onEdit,
                    onDelete: props.onDelete,
                    onView: (employee: Employee) => {
                        // In a real app, you might navigate to a detail page or open a modal
                        import('sonner').then(({ toast }) => {
                            toast.info(`Viewing ${employee.name}'s profile`);
                        });
                    },
                    pagination: props.pagination,
                    onPageChange: props.onPageChange,
                    onPageSizeChange: props.onPageSizeChange,
                    onSearchChange: props.onSearchChange,
                    isLoading: props.isLoading,
                    filterValue: props.filterValue,
                    filters: {
                        search: props.filters.search,
                        department: props.filters.department === 'all-departments' ? '' : props.filters.department,
                        position: props.filters.position === 'all-positions' ? '' : props.filters.position,
                        sort_by: props.filters.sort_by,
                        sort_direction: props.filters.sort_direction,
                    },
                    onFilterChange: props.onFilterChange,
                    onResetFilters: props.onResetFilters,
                    columns: employeeColumns,
                    exportEndpoint: '/api/employees/export',
                    entityType: 'employee',
                }),
                mapFormProps: (props) => ({
                    open: props.open,
                    onOpenChange: props.onOpenChange,
                    employee: props.item,
                    onSubmit: props.onSubmit,
                    isLoading: props.isLoading,
                }),
                getDeleteMessage: (employee) => 
                    `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
            }}
        />
    );
}