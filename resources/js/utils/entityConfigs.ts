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
    T extends Record<string, any>,
    FormData,
    FilterType extends Record<string, any> = Record<string, any>
> extends BaseEntityConfig {
    type: 'complex';
    initialFilters?: FilterType;
    filterFields?: Array<{
        name: keyof FilterType;
        label: string;
        component: React.ReactNode;
    }>;
}

// Union type for all entity configurations
export type EntityConfig<
    T extends Record<string, any> = any,
    FormData = any,
    FilterType extends Record<string, any> = Record<string, any>
> = SimpleEntityConfig | ComplexEntityConfig<T, FormData, FilterType>;

// Predefined configurations for simple entities
export const departmentConfig: SimpleEntityConfig = {
    type: 'simple',
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
        `This action cannot be undone. This will permanently delete ${department.name || 'this department'}'s department record.`,
};

export const positionConfig: SimpleEntityConfig = {
    type: 'simple',
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
        `This action cannot be undone. This will permanently delete ${position.name || 'this position'}'s position record.`,
};

// Import types for complex entities
import { Employee, EmployeeFormData } from '@/types/entity';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';

// Configuration for complex entities (employees)
export interface EmployeeFilters {
    search: string;
    department: string;
    position: string;
    sort_by?: string;
    sort_direction?: string;
}

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
    filterFields: createEmployeeFilterFields() as Array<{
        name: keyof EmployeeFilters;
        label: string;
        component: React.ReactNode;
    }>,
    getDeleteMessage: (employee) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
};
