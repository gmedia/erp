import { type BreadcrumbItem } from '@/types';

// Configuration for simple entities (departments, positions)
export interface SimpleEntityConfig {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    exportEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    filterPlaceholder: string;
    getDeleteMessage: (item: { name: string }) => string;
}

// Predefined configurations for simple entities
export const departmentConfig: SimpleEntityConfig = {
    entityName: 'Department',
    entityNamePlural: 'Departments',
    apiEndpoint: '/api/departments',
    exportEndpoint: '/api/departments/export',
    queryKey: ['departments'],
    breadcrumbs: [
        {
            title: 'Departments',
            href: '/departments',
        },
    ],
    filterPlaceholder: 'Search departments...',
    getDeleteMessage: (department) =>
        `This action cannot be undone. This will permanently delete ${department.name}'s department record.`,
};

export const positionConfig: SimpleEntityConfig = {
    entityName: 'Position',
    entityNamePlural: 'Positions',
    apiEndpoint: '/api/positions',
    exportEndpoint: '/api/positions/export',
    queryKey: ['positions'],
    breadcrumbs: [
        {
            title: 'Positions',
            href: '/positions',
        },
    ],
    filterPlaceholder: 'Search positions...',
    getDeleteMessage: (position) =>
        `This action cannot be undone. This will permanently delete ${position.name}'s position record.`,
};
