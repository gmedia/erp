import { type BreadcrumbItem } from '@/types';
import { type ColumnDef } from '@tanstack/react-table';
import { type FieldDescriptor } from '@/components/common/filters';

// Base configuration interface for all entities
export interface BaseEntityConfig {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    exportEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    getDeleteMessage: (item: { name?: string }) => string;
    initialFilters?: Record<string, any>;
}

// Configuration for entities with custom components
export interface CustomEntityConfig<T = any, FormData = any> extends BaseEntityConfig {
    // Column definitions for the data table
    columns: ColumnDef<T>[];
    // Filter field descriptors
    filterFields: FieldDescriptor[];
    // Form component (can be a React component or import path)
    formComponent: any;
    // Optional entity name for search placeholder
    entityNameForSearch?: string;
}

// Union type for all entity configurations
export type EntityConfig<T = any, FormData = any> = CustomEntityConfig<T, FormData>;

// Helper function to create generic delete messages
const createGenericDeleteMessage = (entityName: string) => (item: { name?: string }) =>
    `This action cannot be undone. This will permanently delete ${item.name || `this ${entityName.toLowerCase()}`}'s ${entityName.toLowerCase()} record.`;

import { createSimpleEntityColumns } from '@/utils/columns';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';

// Helper function to create simple entity configs
const createSimpleEntityConfig = <T extends { name: string; created_at: string; updated_at: string }>(
    entityName: string,
    entityNamePlural: string,
    apiBase: string,
    filterPlaceholder: string
): CustomEntityConfig<T> => ({
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
    initialFilters: { search: '' },
    columns: createSimpleEntityColumns<T>(),
    filterFields: createSimpleEntityFilterFields(filterPlaceholder),
    formComponent: SimpleEntityForm,
    entityNameForSearch: entityName.toLowerCase(),
    getDeleteMessage: createGenericDeleteMessage(entityName),
});

// Predefined configurations for simple entities
export const departmentConfig = createSimpleEntityConfig(
    'Department',
    'Departments',
    'departments',
    'Search departments...'
);

export const positionConfig = createSimpleEntityConfig(
    'Position',
    'Positions',
    'positions',
    'Search positions...'
);

// Configuration for complex entities (employees)
export const employeeConfig: CustomEntityConfig = {
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
    columns: employeeColumns,
    filterFields: createEmployeeFilterFields(),
    formComponent: EmployeeForm,
    entityNameForSearch: 'employee',
    getDeleteMessage: (employee: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
};
