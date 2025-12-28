'use client';

import React from 'react';
import { CrudPage } from '@/components/common/CrudPage';
import { DataTable } from '@/components/common/DataTableCore';
import { EntityConfig } from '@/utils/entityConfigs';

/**
 * Creates a CRUD page component based on the provided entity configuration.
 *
 * This factory function creates a complete CRUD interface using the configuration
 * provided. The configuration includes all necessary components, columns, filters,
 * and form handling.
 *
 * @param config - The entity configuration containing all metadata and components
 * @returns A React component function that renders the complete CRUD page
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

    // Create the page component using the configuration
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
                        initialFilters: config.initialFilters,

                        DataTableComponent: DataTable,
                        FormComponent: config.formComponent,

                        mapDataTableProps: (props) => ({
                            ...props,
                            columns: config.columns,
                            exportEndpoint: config.exportEndpoint,
                            filterFields: config.filterFields,
                            entityName: config.entityNameForSearch,
                        }),

                        mapFormProps: (crudProps) => {
                            // Handle different form component prop requirements
                            if (config.formComponent.name === 'SimpleEntityForm') {
                                return {
                                    open: crudProps.open,
                                    onOpenChange: crudProps.onOpenChange,
                                    entity: crudProps.item ? { name: crudProps.item.name } : null,
                                    onSubmit: crudProps.onSubmit,
                                    isLoading: crudProps.isLoading,
                                    entityName: config.entityName,
                                };
                            } else {
                                // Employee form and other complex forms
                                return {
                                    open: crudProps.open,
                                    onOpenChange: crudProps.onOpenChange,
                                    [config.entityName.toLowerCase()]: crudProps.item,
                                    onSubmit: crudProps.onSubmit,
                                    isLoading: crudProps.isLoading,
                                };
                            }
                        },

                        getDeleteMessage: config.getDeleteMessage,
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
