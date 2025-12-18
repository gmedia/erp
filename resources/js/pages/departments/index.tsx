'use client';

import { SimpleEntityIndex } from '@/components/common/SimpleEntityIndex';
import departments from '@/routes/departments';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments.index().url,
    },
];

export default function DepartmentIndex() {
    return (
        <SimpleEntityIndex
            entityName="Department"
            entityNamePlural="Departments"
            apiEndpoint="/api/departments"
            breadcrumbs={breadcrumbs}
        />
    );
}
