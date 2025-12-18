'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { DepartmentForm } from '@/components/departments/DepartmentForm';
import departments from '@/routes/departments';
import { Department, DepartmentFormData } from '@/types/department';
import { type BreadcrumbItem } from '@/types';
import { createSimpleEntityColumns } from '@/utils/columns';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments.index().url,
    },
];

export default function DepartmentIndex() {
    const columns = createSimpleEntityColumns<Department>();

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

                mapDataTableProps: (props) => ({
                    ...props,
                    columns,
                    exportEndpoint: '/api/departments/export',
                    entityName: 'Department',
                }),

                mapFormProps: (props) => ({
                    ...props,
                    department: props.item,
                }),
            }}
        />
    );
}
