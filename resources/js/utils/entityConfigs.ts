import React from 'react';
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

// Configuration for simple entities (departments, positions)
export interface SimpleEntityConfig extends BaseEntityConfig {
    type: 'simple';
    filterPlaceholder: string;
}

// Configuration for complex entities (employees)
export interface ComplexEntityConfig<
    TEntity = Record<string, unknown> & { id: number; name?: string },
    TFormData = unknown,
    TFilterType = Record<string, unknown>
> extends BaseEntityConfig {
    type: 'complex';
    initialFilters?: TFilterType;
    filterFields?: Array<{
        name: keyof TFilterType;
        label: string;
        component: React.ReactNode;
    }>;
}

// Union type for all entity configurations
export type EntityConfig<
    T = unknown,
    FormData = unknown,
    FilterType = unknown
> = SimpleEntityConfig | ComplexEntityConfig<T & { id: number; name?: string }, FormData, FilterType>;

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

// Import types for complex entities
import { Employee, EmployeeFormData } from '@/types/entity';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';
import { registerComplexEntity } from '@/components/common/SimpleEntityCrudPage';

// Import complex entity components
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employeeColumns } from '@/components/employees/EmployeeColumns';

// Configuration for complex entities (employees)
export interface EmployeeFilters {
    search: string;
    department: string;
    position: string;
    sort_by?: string;
    sort_direction?: string;
}

// Register Employee components
registerComplexEntity('Employee', {
    FormComponent: EmployeeForm,
    columns: employeeColumns,
    filterFields: createEmployeeFilterFields().map(field => ({
        name: field.name as keyof EmployeeFilters,
        label: field.label,
        component: field.component,
    })),
});

export const employeeConfig: ComplexEntityConfig<Employee, EmployeeFormData, EmployeeFilters> = {
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
