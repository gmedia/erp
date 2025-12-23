import { type BreadcrumbItem } from '@/types';

// Base configuration interface for all entities
export interface BaseEntityConfig {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    exportEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    getDeleteMessage: (item: { name?: string }) => string;
}

// Configuration for simple entities (departments, positions) - just name field
export interface SimpleEntityConfig extends BaseEntityConfig {
    type: 'simple';
    filterPlaceholder: string;
}

// Configuration for complex entities (employees) - multiple fields with custom components
export interface ComplexEntityConfig extends BaseEntityConfig {
    type: 'complex';
    initialFilters?: Record<string, any>;
}

// Union type for all entity configurations
export type EntityConfig = SimpleEntityConfig | ComplexEntityConfig;

// Helper function to create generic delete messages
const createGenericDeleteMessage = (entityName: string) => (item: { name?: string }) =>
    `This action cannot be undone. This will permanently delete ${item.name || `this ${entityName.toLowerCase()}`}'s ${entityName.toLowerCase()} record.`;

// Helper function to create simple entity configs
const createSimpleEntityConfig = (
    entityName: string,
    entityNamePlural: string,
    apiBase: string,
    filterPlaceholder: string
): SimpleEntityConfig => ({
    type: 'simple',
    entityName,
    entityNamePlural,
    apiEndpoint: `/api/${apiBase}`,
    exportEndpoint: `/api/${apiBase}/export`,
    queryKey: [apiBase],
    breadcrumbs: [
        {
            title: entityNamePlural,
            href: `/${apiBase}`,
        },
    ],
    filterPlaceholder,
    getDeleteMessage: createGenericDeleteMessage(entityName),
});

// Predefined configurations for simple entities
export const departmentConfig: SimpleEntityConfig = createSimpleEntityConfig(
    'Department',
    'Departments',
    'departments',
    'Search departments...'
);

export const positionConfig: SimpleEntityConfig = createSimpleEntityConfig(
    'Position',
    'Positions',
    'positions',
    'Search positions...'
);

// Configuration for complex entities (employees)
export const employeeConfig: ComplexEntityConfig = {
    type: 'complex',
    entityName: 'Employee',
    entityNamePlural: 'Employees',
    apiEndpoint: '/api/employees',
    exportEndpoint: '/api/employees/export',
    queryKey: ['employees'],
    breadcrumbs: [
        {
            title: 'Employees',
            href: '/employees',
        },
    ],
    initialFilters: {
        search: '',
        department: '',
        position: '',
    },
    getDeleteMessage: (employee) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
};
