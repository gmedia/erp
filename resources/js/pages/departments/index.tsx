'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { createDepartmentFilterFields } from '@/components/departments/DepartmentFilters';
import { DepartmentForm } from '@/components/departments/DepartmentForm';
import departments from '@/routes/departments';
import { type BreadcrumbItem } from '@/types';
import { departmentColumns } from '@/components/departments/DepartmentColumns';

interface Department {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface DepartmentFormData {
    name: string;
}

interface DepartmentFilters {
    search: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments.index().url,
    },
];

export default function DepartmentIndex() {
    return (
        <CrudPage<Department, DepartmentFormData, DepartmentFilters>
            config={{
                entityName: 'Department',
                entityNamePlural: 'Departments',
                apiEndpoint: '/api/departments',
                queryKey: ['departments'],
                breadcrumbs,
                DataTableComponent: GenericDataTable,
                FormComponent: DepartmentForm,

                initialFilters: {
                    search: '',
                },

                mapDataTableProps: (props) => ({
                    ...props,
                    columns: departmentColumns,
                    exportEndpoint: '/api/departments/export',
                    filterFields: createDepartmentFilterFields(),
                }),

                mapFormProps: (props) => ({
                    ...props,
                    department: props.item,
                }),

                getDeleteMessage: (department) =>
                    `This action cannot be undone. This will permanently delete ${department.name}'s department record.`,
            }}
        />
    );
}
