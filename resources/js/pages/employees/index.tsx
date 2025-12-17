'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import employees from '@/routes/employees';
import { Employee, EmployeeFormData } from '@/types/employee';
import { type BreadcrumbItem } from '@/types';
import { employeeColumns } from '@/components/employees/EmployeeColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: employees.index().url,
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

                // Include initial filters for department and position
                initialFilters: {
                    search: '',
                    department: '',
                    position: '',
                    sort_by: undefined,
                    sort_direction: undefined,
                },

                // Simplified prop mapping using spread operator
                mapDataTableProps: (props) => ({
                    ...props,
                    columns: employeeColumns,
                    exportEndpoint: '/api/employees/export',
                    entityType: 'employee',
                }),

                mapFormProps: (props) => ({
                    ...props,
                    employee: props.item,
                }),

                getDeleteMessage: (employee) =>
                    `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
            }}
        />
    );
}
