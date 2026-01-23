import { type FormComponentType } from '@/components/common/EntityCrudPage';
import { type FieldDescriptor } from '@/components/common/filters';
import { type FilterState } from '@/hooks/useCrudFilters';
import { type BreadcrumbItem } from '@/types';
import { type EntityWithId } from '@/types/entity';
import { type ColumnDef } from '@tanstack/react-table';
import * as React from 'react';

// Base configuration interface for all entities with improved typing
export interface BaseEntityConfig<
    FilterType extends FilterState = FilterState,
> {
    readonly entityName: string;
    readonly entityNamePlural: string;
    readonly apiEndpoint: string;
    readonly exportEndpoint: string;
    readonly queryKey: readonly string[];
    readonly breadcrumbs: readonly BreadcrumbItem[];
    readonly getDeleteMessage: (item: Record<string, unknown>) => string;
    readonly initialFilters?: FilterType;
}

// Configuration for entities with custom components and stricter typing
export interface CustomEntityConfig<
    T = Record<string, unknown>,
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    FormData = Record<string, unknown>,
    FilterType extends FilterState = FilterState,
> extends BaseEntityConfig<FilterType> {
    // Column definitions for the data table
    readonly columns: ColumnDef<T>[];
    // Filter field descriptors
    readonly filterFields: readonly FieldDescriptor[];
    // Form component (can be a React component or import path)
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    readonly formComponent: React.ComponentType<any>;
    // Form type for proper prop mapping
    readonly formType: FormComponentType;
    // Optional entity name for search placeholder
    readonly entityNameForSearch?: string;
    // Optional view modal component for displaying item details
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    readonly viewModalComponent?: React.ComponentType<any>;
}

// Union type for all entity configurations with improved typing
export type EntityConfig<
    T extends EntityWithId = EntityWithId,
    FormData = Record<string, unknown>,
    FilterType extends FilterState = FilterState,
> = CustomEntityConfig<T, FormData, FilterType>;

import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { SimpleEntityViewModal } from '@/components/common/SimpleEntityViewModal';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { EmployeeViewModal } from '@/components/employees/EmployeeViewModal';
import { customerColumns } from '@/components/customers/CustomerColumns';
import { createCustomerFilterFields } from '@/components/customers/CustomerFilters';
import { CustomerForm } from '@/components/customers/CustomerForm';
import { CustomerViewModal } from '@/components/customers/CustomerViewModal';
import { createSimpleEntityColumns } from '@/utils/columns';

// Helper function to create generic delete messages
const createGenericDeleteMessage =
    (entityName: string) => (item: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${item.name || `this ${entityName.toLowerCase()}`}'s ${entityName.toLowerCase()} record.`;

// Configuration builder options
export interface SimpleEntityConfigOptions {
    entityName: string;
    entityNamePlural: string;
    apiBase: string;
    filterPlaceholder: string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewModalComponent?: React.ComponentType<any>;
}

export interface ComplexEntityConfigOptions<T = Record<string, unknown>> {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    exportEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    initialFilters: Record<string, string | number | undefined>;
    columns: ColumnDef<T>[];
    filterFields: FieldDescriptor[];
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    formComponent: React.ComponentType<any>;
    formType: FormComponentType;
    entityNameForSearch?: string;
    getDeleteMessage: (item: Record<string, unknown>) => string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    viewModalComponent?: React.ComponentType<any>;
}

// Factory to create a bound SimpleEntityViewModal for a specific entity
function createSimpleEntityViewModal(entityName: string) {
    return function BoundSimpleEntityViewModal(props: {
        open: boolean;
        onClose: () => void;
        item: {
            id: number;
            name: string;
            created_at: string;
            updated_at: string;
        } | null;
    }) {
        return React.createElement(SimpleEntityViewModal, {
            ...props,
            entityName,
        });
    };
}

// Enhanced helper function to create simple entity configs with consistent structure
function createSimpleEntityConfig<
    T extends {
        id: number;
        name: string;
        created_at: string;
        updated_at: string;
    },
>(
    options: Omit<SimpleEntityConfigOptions, 'viewModalComponent'>,
): CustomEntityConfig<T> {
    const { entityName, entityNamePlural, apiBase, filterPlaceholder } =
        options;

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
        viewModalComponent: createSimpleEntityViewModal(entityName),
    };
}

// Factory function for complex entity configs
function createComplexEntityConfig<T = Record<string, unknown>>(
    options: ComplexEntityConfigOptions<T>,
): CustomEntityConfig<T> {
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
        viewModalComponent: options.viewModalComponent,
    };
}

// Predefined configurations for simple entities
export const departmentConfig = createSimpleEntityConfig({
    entityName: 'Department',
    entityNamePlural: 'Departments',
    apiBase: 'departments',
    filterPlaceholder: 'Search departments...',
});

export const positionConfig = createSimpleEntityConfig({
    entityName: 'Position',
    entityNamePlural: 'Positions',
    apiBase: 'positions',
    filterPlaceholder: 'Search positions...',
});

export const branchConfig = createSimpleEntityConfig({
    entityName: 'Branch',
    entityNamePlural: 'Branches',
    apiBase: 'branches',
    filterPlaceholder: 'Search branches...',
});

// Configuration for complex entities (employees) - using factory for consistency
export const employeeConfig = createComplexEntityConfig({
    entityName: 'Employee',
    entityNamePlural: 'Employees',
    apiEndpoint: '/api/employees',
    exportEndpoint: '/api/employees/export',
    queryKey: ['employees'],
    breadcrumbs: [{ title: 'Employees', href: '/employees' }],
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
    viewModalComponent: EmployeeViewModal,
    getDeleteMessage: (employee: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${employee.name}'s employee record.`,
});

// Configuration for complex entities (customers) - using factory for consistency
export const customerConfig = createComplexEntityConfig({
    entityName: 'Customer',
    entityNamePlural: 'Customers',
    apiEndpoint: '/api/customers',
    exportEndpoint: '/api/customers/export',
    queryKey: ['customers'],
    breadcrumbs: [{ title: 'Customers', href: '/customers' }],
    initialFilters: {
        search: '',
        branch: '',
        customer_type: '',
        status: '',
    },
    columns: customerColumns,
    filterFields: createCustomerFilterFields(),
    formComponent: CustomerForm,
    formType: 'complex',
    entityNameForSearch: 'customer',
    viewModalComponent: CustomerViewModal,
    getDeleteMessage: (customer: { name?: string }) =>
        `This action cannot be undone. This will permanently delete ${customer.name}'s customer record.`,
});
