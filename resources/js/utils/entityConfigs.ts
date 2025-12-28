import { type BreadcrumbItem } from '@/types';
import { type ColumnDef } from '@tanstack/react-table';
import { type FieldDescriptor } from '@/components/common/filters';
import { type FormComponentType } from '@/components/common/EntityCrudPage';

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
    // Form type for proper prop mapping
    formType: FormComponentType;
    // Optional entity name for search placeholder
    entityNameForSearch?: string;
}

// Union type for all entity configurations
export type EntityConfig<T = any, FormData = any> = CustomEntityConfig<T, FormData>;

import { createSimpleEntityColumns } from '@/utils/columns';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';

// Helper function to create generic delete messages
const createGenericDeleteMessage = (entityName: string) => (item: { name?: string }) =>
    `This action cannot be undone. This will permanently delete ${item.name || `this ${entityName.toLowerCase()}`}'s ${entityName.toLowerCase()} record.`;

// Configuration builder options
export interface SimpleEntityConfigOptions {
    entityName: string;
    entityNamePlural: string;
    apiBase: string;
    filterPlaceholder: string;
}

export interface ComplexEntityConfigOptions<T = any, FormData = any> {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    exportEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    initialFilters: Record<string, any>;
    columns: ColumnDef<T>[];
    filterFields: FieldDescriptor[];
    formComponent: any;
    formType: FormComponentType;
    entityNameForSearch?: string;
    getDeleteMessage: (item: { name?: string }) => string;
}

// Enhanced helper function to create simple entity configs with consistent structure
function createSimpleEntityConfig<T extends { name: string; created_at: string; updated_at: string }>(
    options: SimpleEntityConfigOptions
): CustomEntityConfig<T> {
    const { entityName, entityNamePlural, apiBase, filterPlaceholder } = options;

    return {
        entityName,
        entityNamePlural,
        apiEndpoint: `/api/${apiBase}`,
        exportEndpoint: `/api/${apiBase}/export`,
        queryKey: [apiBase],
        breadcrumbs: [{ title: entityNamePlural, href: `/${apiBase}` }],
        initialFilters: { search: '' },
        columns: createSimpleEntityColumns<T>(),
        filterFields: createSimpleEntityFilterFields(filterPlaceholder),
        formComponent: SimpleEntityForm,
        formType: 'simple',
        entityNameForSearch: entityName.toLowerCase(),
        getDeleteMessage: createGenericDeleteMessage(entityName),
    };
}

// Factory function for complex entity configs
function createComplexEntityConfig<T = any, FormData = any>(
    options: ComplexEntityConfigOptions<T, FormData>
): CustomEntityConfig<T, FormData> {
    return {
        entityName: options.entityName,
        entityNamePlural: options.entityNamePlural,
        apiEndpoint: options.apiEndpoint,
        exportEndpoint: options.exportEndpoint,
        queryKey: options.queryKey,
        breadcrumbs: options.breadcrumbs,
        initialFilters: options.initialFilters,
        columns: options.columns,
        filterFields: options.filterFields,
        formComponent: options.formComponent,
        formType: options.formType,
        entityNameForSearch: options.entityNameForSearch,
        getDeleteMessage: options.getDeleteMessage,
    };
}

// Predefined configurations for simple entities
export const departmentConfig = createSimpleEntityConfig({
    entityName: 'Department',
    entityNamePlural: 'Departments',
    apiBase: 'departments',
    filterPlaceholder: 'Search departments...'
});

export const positionConfig = createSimpleEntityConfig({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    apiBase: 'positions',
    filterPlaceholder: 'Search positions...'
});

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
    formType: 'complex',
    entityNameForSearch: 'employee',
    getDeleteMessage: (employee: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
};
