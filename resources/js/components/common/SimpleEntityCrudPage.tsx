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
import { type ColumnDef } from '@tanstack/react-table';
import { type FieldDescriptor } from './filters';

// Base interface for all entity component configurations
interface EntityComponentConfig<T = any> {
    DataTableComponent: React.ComponentType<any>;
    FormComponent: React.ComponentType<any>;
    getColumns: () => ColumnDef<T>[];
    getFilterFields: (config: EntityConfig) => FieldDescriptor[];
    mapFormProps: (config: EntityConfig, crudProps: any) => any;
    supportsConfig: (config: EntityConfig) => boolean;
}

// Registry class for entity component configurations
class EntityComponentRegistry {
    private registry = new Map<string, EntityComponentConfig>();

    register(key: string, config: EntityComponentConfig): void {
        this.registry.set(key, config);
    }

    get(key: string): EntityComponentConfig | undefined {
        return this.registry.get(key);
    }

    has(key: string): boolean {
        return this.registry.has(key);
    }
}

// Create and configure the registry
const entityRegistry = new EntityComponentRegistry();

// Register simple entity components (for departments, positions)
entityRegistry.register('simple', {
    DataTableComponent: DataTable,
    FormComponent: SimpleEntityForm,
    getColumns: createSimpleEntityColumns,
    getFilterFields: (config: EntityConfig) =>
        createSimpleEntityFilterFields((config as SimpleEntityConfig).filterPlaceholder),
    mapFormProps: (config: EntityConfig, crudProps: any) => {
        const simpleConfig = config as SimpleEntityConfig;
        return {
            open: crudProps.open,
            onOpenChange: crudProps.onOpenChange,
            entity: crudProps.item ? { name: crudProps.item.name } : null,
            onSubmit: crudProps.onSubmit,
            isLoading: crudProps.isLoading,
            entityName: simpleConfig.entityName,
        };
    },
    supportsConfig: (config: EntityConfig) => config.type === 'simple',
});

// Register employee entity components
entityRegistry.register('employee', {
    DataTableComponent: DataTable,
    FormComponent: EmployeeForm,
    getColumns: () => employeeColumns,
    getFilterFields: (_config: EntityConfig) => createEmployeeFilterFields(),
    mapFormProps: (config: EntityConfig, crudProps: any) => ({
        open: crudProps.open,
        onOpenChange: crudProps.onOpenChange,
        employee: crudProps.item, // Employee form expects 'employee' prop
        onSubmit: crudProps.onSubmit,
        isLoading: crudProps.isLoading,
    }),
    supportsConfig: (config: EntityConfig) => config.type === 'complex' && config.entityName === 'Employee',
});

/**
 * Unified factory function that creates appropriate CRUD pages based on entity configuration.
 * Uses a registry-based approach for better extensibility and maintainability.
 */
export function createEntityCrudPage(config: EntityConfig) {
    // Determine which component configuration to use based on entity type
    const componentKey = config.type === 'simple' ? 'simple' : 'employee';
    const components = entityRegistry.get(componentKey);

    if (!components) {
        throw new Error(`No component configuration found for entity type: ${config.type} (${config.entityName})`);
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
