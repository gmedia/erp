'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { EmployeeDataTable } from '@/components/employees/EmployeeDataTable';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employees } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Employee, EmployeeFormData } from '@/types/employee';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: employees().url,
    },
];

interface EmployeeFilters {
    search: string;
    department: string;
    position: string;
    sort_by?: string;
    sort_direction?: string;
}

export default function EmployeeIndex() {
    return (
        <CrudPage<Employee, EmployeeFormData, EmployeeFilters>
            config={{
                entityName: 'Employee',
                entityNamePlural: 'Employees',
                apiEndpoint: '/api/employees',
                queryKey: ['employees'],
                breadcrumbs,
                
                // Custom initial filters for employees
                initialFilters: {
                    search: '',
                    department: '',
                    position: '',
                    sort_by: undefined,
                    sort_direction: undefined,
                },
                
                DataTableComponent: EmployeeDataTable,
                FormComponent: EmployeeForm,
                
                // Map the generic props to component-specific props
                mapDataTableProps: (props) => ({
                    data: props.data,
                    onAddEmployee: props.onAdd,
                    onEditEmployee: props.onEdit,
                    onDeleteEmployee: props.onDelete,
                    onViewEmployee: (employee: Employee) => {
                        // In a real app, you might navigate to a detail page or open a modal
                        console.log(`Viewing ${employee.name}'s profile`);
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
                    onFilterChange: (newFilters: Partial<EmployeeFilters>) => {
                        // If only the search term is being updated, reset other filters to avoid
                        // unintended combination of search with stale department/position filters.
                        const isSearchOnly = Object.keys(newFilters).length === 1 && 'search' in newFilters;
                        props.onFilterChange({
                            ...newFilters,
                            ...(isSearchOnly ? { department: '', position: '' } : {}),
                        });
                    },
                    onResetFilters: props.onResetFilters,
                }),
                
                mapFormProps: (props) => ({
                    open: props.open,
                    onOpenChange: props.onOpenChange,
                    employee: props.item,
                    onSubmit: props.onSubmit,
                    isLoading: props.isLoading,
                }),
                
                // Custom delete message
                getDeleteMessage: (employee: Employee) => 
                    `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
            }}
        />
    );
}