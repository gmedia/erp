'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createDepartmentFilterFields } from '@/components/departments/DepartmentFilters';
import { Department, DepartmentFormData, SimpleEntityFilters } from '@/types/entity';
import { type BreadcrumbItem } from '@/types';
import { departmentColumns } from '@/components/departments/DepartmentColumns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: '/departments',
    },
];

export default function DepartmentIndex() {
    return (
        <CrudPage<Department, DepartmentFormData, SimpleEntityFilters>
            config={{
                entityName: 'Department',
                entityNamePlural: 'Departments',
                apiEndpoint: '/api/departments',
                queryKey: ['departments'],
                breadcrumbs,

                DataTableComponent: GenericDataTable,
                FormComponent: SimpleEntityForm,

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
                    entity: props.item,
                    entityName: 'Department',
                }),

                getDeleteMessage: (department) =>
                    `This action cannot be undone. This will permanently delete ${department.name}'s department record.`,
            }}
        />
    );
}
