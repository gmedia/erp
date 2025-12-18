'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import employees from '@/routes/employees';
import { Employee, EmployeeFormData } from '@/types/employee';
import { type BreadcrumbItem } from '@/types';
import { employeeColumns } from '@/components/employees/EmployeeColumns';

interface EmployeeFilters {
    search: string;
    department: string;
    position: string;
    sort_by?: string;
    sort_direction?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employees',
        href: employees.index().url,
    },
];

export default function EmployeeIndex() {
    return (
        <CrudPage<Employee, EmployeeFormData, EmployeeFilters>
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
                },

                mapDataTableProps: (props) => ({
                    ...props,
                    columns: employeeColumns,
                    exportEndpoint: '/api/employees/export',
                    filterFields: createEmployeeFilterFields(),
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
