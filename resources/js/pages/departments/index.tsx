'use client';

import { SimpleEntityIndex } from '@/components/common/SimpleEntityIndex';
import departments from '@/routes/departments';
import { type BreadcrumbItem } from '@/types';
import { Department } from '@/types/department';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: departments.index().url,
    },
];

export default function DepartmentIndex() {
    return (
        <SimpleEntityIndex<Department>
            entityName="Department"
            entityNamePlural="Departments"
            routes={departments}
            apiEndpoint="/api/departments"
            breadcrumbs={breadcrumbs}
        />
    );
}
