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

// Base interface for entity component configurations
interface BaseEntityComponents<T> {
    DataTableComponent: React.ComponentType<any>;
    FormComponent: React.ComponentType<any>;
    getColumns: () => ColumnDef<T>[];
    getFilterFields: (config: EntityConfig) => FieldDescriptor[];
}

// Specialized interface for different entity types
interface SimpleEntityComponents extends BaseEntityComponents<{ name: string; created_at: string; updated_at: string }> {
    type: 'simple';
    mapFormProps: (config: SimpleEntityConfig, crudProps: any) => any;
}

interface EmployeeEntityComponents extends BaseEntityComponents<any> {
    type: 'employee';
    mapFormProps: (config: ComplexEntityConfig, crudProps: any) => any;
}

// Union type for all entity component configurations
type EntityComponents = SimpleEntityComponents | EmployeeEntityComponents;

// Registry class for better type safety and extensibility
class EntityRegistry {
    private registry = new Map<string, EntityComponents>();

    register(key: string, components: EntityComponents): void {
        this.registry.set(key, components);
    }

    get<T extends EntityComponents>(key: string): T | undefined {
        return this.registry.get(key) as T | undefined;
    }

    has(key: string): boolean {
        return this.registry.has(key);
    }

    keys(): string[] {
        return Array.from(this.registry.keys());
    }
}

// Create and configure the registry
const entityRegistry = new EntityRegistry();

// Register simple entity components
entityRegistry.register('simple', {
    type: 'simple',
    DataTableComponent: DataTable,
    FormComponent: SimpleEntityForm,
    getColumns: createSimpleEntityColumns,
    getFilterFields: (config: EntityConfig) =>
        createSimpleEntityFilterFields((config as SimpleEntityConfig).filterPlaceholder),
    mapFormProps: (config: SimpleEntityConfig, crudProps: any) => ({
        open: crudProps.open,
        onOpenChange: crudProps.onOpenChange,
        entity: crudProps.item ? { name: crudProps.item.name } : null,
        onSubmit: crudProps.onSubmit,
        isLoading: crudProps.isLoading,
        entityName: config.entityName,
    }),
});

// Register employee entity components
entityRegistry.register('Employee', {
    type: 'employee',
    DataTableComponent: DataTable,
    FormComponent: EmployeeForm,
    getColumns: () => employeeColumns,
    getFilterFields: (_config: EntityConfig) => createEmployeeFilterFields(),
    mapFormProps: (_config: ComplexEntityConfig, crudProps: any) => ({
        open: crudProps.open,
        onOpenChange: crudProps.onOpenChange,
        employee: crudProps.item, // Employee form expects 'employee' prop
        onSubmit: crudProps.onSubmit,
        isLoading: crudProps.isLoading,
    }),
});

/**
 * Unified factory function that creates appropriate CRUD pages based on entity configuration.
 * Eliminates code duplication by using a registry-based approach.
 */
export function createEntityCrudPage(config: EntityConfig) {
    // Determine which component configuration to use
    const componentKey = config.type === 'simple' ? 'simple' : config.entityName;
    const components = entityRegistry.get(componentKey);

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

                    mapFormProps: (crudProps) => {
                        if (components.type === 'simple') {
                            return (components as SimpleEntityComponents).mapFormProps(config as SimpleEntityConfig, crudProps);
                        } else {
                            return (components as EmployeeEntityComponents).mapFormProps(config as ComplexEntityConfig, crudProps);
                        }
                    },

                    getDeleteMessage: (item) => config.getDeleteMessage(item),
                }}
            />
        );
    };
}
