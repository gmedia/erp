'use client';

import { createSimpleEntityCrudPage } from '@/components/common/SimpleEntityCrudPage';
import { Department, DepartmentFormData, SimpleEntityFilters } from '@/types/entity';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Departments',
        href: '/departments',
    },
];

export default createSimpleEntityCrudPage<Department, DepartmentFormData, SimpleEntityFilters>({
    entityName: 'Department',
    entityNamePlural: 'Departments',
    apiEndpoint: '/api/departments',
    queryKey: ['departments'],
    breadcrumbs,
    exportEndpoint: '/api/departments/export',
    filterPlaceholder: 'Search departments...',
    getDeleteMessage: (department) =>
        `This action cannot be undone. This will permanently delete ${department.name}'s department record.`,
});
