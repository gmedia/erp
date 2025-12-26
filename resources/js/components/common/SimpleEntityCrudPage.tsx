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

// Type-safe interface for entity component configurations
interface EntityComponentConfig<
    TEntity = any,
    TFormData = any,
    TFilters = Record<string, any>
> {
    DataTableComponent: React.ComponentType<any>;
    FormComponent: React.ComponentType<any>;
    getColumns: () => ColumnDef<TEntity>[];
    getFilterFields: (config: EntityConfig) => FieldDescriptor[];
    mapFormProps: (config: EntityConfig, crudProps: any) => any;
    supportsConfig: (config: EntityConfig) => boolean;
}

/**
 * Registry class for managing entity component configurations.
 * Provides a centralized way to register and retrieve component configurations
 * based on entity configuration types.
 */
class EntityComponentRegistry {
    private registry = new Map<string, EntityComponentConfig>();

    /**
     * Registers a component configuration with the given key.
     * @param key - Unique identifier for the component configuration
     * @param config - The component configuration to register
     */
    register(key: string, config: EntityComponentConfig): void {
        if (this.registry.has(key)) {
            console.warn(`Component configuration with key '${key}' is being overwritten`);
        }
        this.registry.set(key, config);
    }

    /**
     * Retrieves a component configuration by key.
     * @param key - The key of the component configuration to retrieve
     * @returns The component configuration or undefined if not found
     */
    get(key: string): EntityComponentConfig | undefined {
        return this.registry.get(key);
    }

    /**
     * Finds the appropriate component configuration for the given entity config.
     * @param config - The entity configuration to match against
     * @returns The matching component configuration or undefined if none found
     */
    findForConfig(config: EntityConfig): EntityComponentConfig | undefined {
        for (const [, componentConfig] of this.registry) {
            if (componentConfig.supportsConfig(config)) {
                return componentConfig;
            }
        }
        return undefined;
    }

    /**
     * Gets all registered component configurations.
     * @returns Array of all registered component configurations
     */
    getAll(): EntityComponentConfig[] {
        return Array.from(this.registry.values());
    }

    /**
     * Clears all registered component configurations.
     */
    clear(): void {
        this.registry.clear();
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

// Register complex entity components (for employees and future complex entities)
entityRegistry.register('complex', {
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
    supportsConfig: (config: EntityConfig) => config.type === 'complex',
});

/**
 * Creates a CRUD page component based on the provided entity configuration.
 *
 * This factory function uses a registry-based approach to dynamically select
 * the appropriate components (DataTable, Form, columns, filters) based on
 * the entity type. It supports both simple entities (name only) and complex
 * entities (multiple fields with custom forms).
 *
 * @param config - The entity configuration containing metadata and settings
 * @returns A React component function that renders the complete CRUD page
 * @throws Error if no suitable component configuration is found for the entity type
 *
 * @example
 * ```tsx
 * // Simple entity (departments, positions)
 * export default createEntityCrudPage(departmentConfig);
 *
 * // Complex entity (employees)
 * export default createEntityCrudPage(employeeConfig);
 * ```
 */
export function createEntityCrudPage(config: EntityConfig): () => React.JSX.Element {
    // Validate input configuration
    if (!config) {
        throw new Error('Entity configuration is required');
    }

    if (!config.entityName || !config.apiEndpoint) {
        throw new Error('Entity configuration must include entityName and apiEndpoint');
    }

    // Find the appropriate component configuration based on the entity config
    const components = entityRegistry.findForConfig(config);

    if (!components) {
        const availableTypes = entityRegistry.getAll()
            .map(c => `'${c.supportsConfig.toString()}'`)
            .join(', ');
        throw new Error(
            `No component configuration found for entity type: ${config.type} (${config.entityName}). ` +
            `Available configurations support: ${availableTypes}`
        );
    }

    // Create the page component with unified logic
    const EntityCrudPage = (): React.JSX.Element => {
        try {
            return (
                <CrudPage
                    config={{
                        entityName: config.entityName,
                        entityNamePlural: config.entityNamePlural,
                        apiEndpoint: config.apiEndpoint,
                        queryKey: config.queryKey,
                        breadcrumbs: config.breadcrumbs,
                        initialFilters: config.type === 'simple'
                            ? { search: '' }
                            : (config as ComplexEntityConfig).initialFilters || { search: '' },

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
        } catch (error) {
            console.error(`Error rendering CRUD page for ${config.entityName}:`, error);
            throw error;
        }
    };

    // Add display name for better debugging
    EntityCrudPage.displayName = `${config.entityName}CrudPage`;

    return EntityCrudPage;
}
