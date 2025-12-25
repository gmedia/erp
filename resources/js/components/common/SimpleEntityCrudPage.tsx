'use client';

import React from 'react';
import { CrudPage } from '@/components/common/CrudPage';
import { DataTable } from '@/components/common/DataTableCore';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { createSimpleEntityColumns } from '@/utils/columns';
import { EntityConfig, SimpleEntityConfig, ComplexEntityConfig } from '@/utils/entityConfigs';
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employeeColumns } from '@/components/employees/EmployeeColumns';
import { createEmployeeFilterFields } from '@/components/employees/EmployeeFilters';

// Configuration for entity-specific components and behavior
interface EntityComponents {
    DataTableComponent: React.ComponentType<any>;
    FormComponent: React.ComponentType<any>;
    getColumns: () => any[];
    getFilterFields: (config: EntityConfig) => any[];
    mapFormProps: (config: EntityConfig, crudProps: any) => any;
}

// Registry of entity-specific configurations
const entityRegistry: Record<string, EntityComponents> = {
    simple: {
        DataTableComponent: DataTable,
        FormComponent: SimpleEntityForm,
        getColumns: createSimpleEntityColumns,
        getFilterFields: (config: EntityConfig) => createSimpleEntityFilterFields((config as SimpleEntityConfig).filterPlaceholder),
        mapFormProps: (config: EntityConfig, crudProps: any) => ({
            open: crudProps.open,
            onOpenChange: crudProps.onOpenChange,
            entity: crudProps.item ? { name: crudProps.item.name } : null,
            onSubmit: crudProps.onSubmit,
            isLoading: crudProps.isLoading,
            entityName: config.entityName,
        }),
    },
    Employee: {
        DataTableComponent: DataTable,
        FormComponent: EmployeeForm,
        getColumns: () => employeeColumns,
        getFilterFields: (_config: EntityConfig) => createEmployeeFilterFields(),
        mapFormProps: (_config: EntityConfig, crudProps: any) => ({
            open: crudProps.open,
            onOpenChange: crudProps.onOpenChange,
            employee: crudProps.item, // Employee form expects 'employee' prop
            onSubmit: crudProps.onSubmit,
            isLoading: crudProps.isLoading,
        }),
    },
};

/**
 * Unified factory function that creates appropriate CRUD pages based on entity configuration.
 * Eliminates code duplication by using a registry-based approach.
 */
export function createEntityCrudPage(config: EntityConfig) {
    // Determine which component configuration to use
    const componentKey = config.type === 'simple' ? 'simple' : config.entityName;
    const components = entityRegistry[componentKey];

    if (!components) {
        throw new Error(`No component configuration found for entity: ${config.entityName} (type: ${config.type})`);
    }

    // Create the page component with unified logic
    return function EntityCrudPage() {
        return (
            <CrudPage
                config={{
                    entityName: config.entityName,
                    entityNamePlural: config.entityNamePlural,
                    apiEndpoint: config.apiEndpoint,
                    queryKey: config.queryKey,
                    breadcrumbs: config.breadcrumbs,
                    initialFilters: config.type === 'simple' ? { search: '' } : (config as ComplexEntityConfig).initialFilters || { search: '' },

                    DataTableComponent: components.DataTableComponent,
                    FormComponent: components.FormComponent,

                    mapDataTableProps: (props) => ({
                        ...props,
                        columns: components.getColumns(),
                        exportEndpoint: config.exportEndpoint,
                        filterFields: components.getFilterFields(config),
                        entityName: config.entityName,
                    }),

                    mapFormProps: (crudProps) => components.mapFormProps(config, crudProps),

                    getDeleteMessage: (item) => config.getDeleteMessage(item),
                }}
            />
        );
    };
}
