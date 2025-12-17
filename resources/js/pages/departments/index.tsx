'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { DepartmentForm } from '@/components/departments/DepartmentForm';
import departments from '@/routes/departments';
import { type BreadcrumbItem } from '@/types';
import { Department, DepartmentFormData } from '@/types/department';
import { departmentColumns } from '@/components/departments/DepartmentColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments.index().url,
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

                // Simplified prop mapping using spread operator
                mapDataTableProps: (props) => ({
                    ...props,
                    columns: departmentColumns,
                    exportEndpoint: '/api/departments/export',
                    entityType: 'department',
                }),

                mapFormProps: (props) => ({
                    ...props,
                    department: props.item,
                }),
            }}
        />
    );
}
