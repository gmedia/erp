'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { DataTable } from '@/components/common/DataTableCore';
import { CustomEntityConfig } from '@/utils/entityConfigs';
import { ColumnDef } from '@tanstack/react-table';
import React from 'react';

// Form component prop interfaces for better type safety
export interface BaseFormProps<FormData = unknown> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSubmit: (data: FormData) => void;
    isLoading: boolean;
}

export interface SimpleFormProps extends BaseFormProps<{ name: string }> {
    entity: { name: string } | null;
    entityName: string;
}

export interface ComplexFormProps<
    T extends { id: number; name: string },
    FormData = unknown,
> extends BaseFormProps<FormData> {
    [key: string]: unknown; // Dynamic prop name based on entity
}

export type FormComponentType = 'simple' | 'complex';

// Configuration interface for the CRUD page factory
export interface EntityCrudConfig<
    T extends { id: number; name: string },
    FormData = unknown,
> extends Omit<CustomEntityConfig<T, FormData>, 'columns'> {
    columns: ColumnDef<T>[];
}

// Form props mapper factory - simplified to avoid generic complexity
function createFormPropsMapper(config: EntityCrudConfig<any, any>) {
    return (crudProps: {
        open: boolean;
        onOpenChange: (open: boolean) => void;
        item?: any;
        onSubmit: (data: any) => void;
        isLoading: boolean;
    }) => {
        const baseProps = {
            open: crudProps.open,
            onOpenChange: crudProps.onOpenChange,
            onSubmit: crudProps.onSubmit,
            isLoading: crudProps.isLoading,
        };

        if (config.formType === 'simple') {
            return {
                ...baseProps,
                entity: crudProps.item ? { name: crudProps.item.name } : null,
                entityName: config.entityName,
            };
        } else {
            // For complex forms, use the entity name in lowercase as the prop key
            const entityKey = config.entityName.toLowerCase();
            return {
                ...baseProps,
                [entityKey]: crudProps.item,
            };
        }
    };
}

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
export function createEntityCrudPage<
    T extends { id: number; name: string },
    FormData = unknown,
>(config: EntityCrudConfig<T, FormData>): () => React.JSX.Element {
    // Validate input configuration
    if (!config) {
        throw new Error('Entity configuration is required');
    }

    if (!config.entityName || !config.apiEndpoint) {
        throw new Error(
            'Entity configuration must include entityName and apiEndpoint',
        );
    }

    if (!config.formType) {
        throw new Error('Entity configuration must include formType');
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

                        mapFormProps: createFormPropsMapper(config),

                        getDeleteMessage: config.getDeleteMessage,
                    }}
                />
            );
        } catch (error) {
            console.error(
                `Error rendering CRUD page for ${config.entityName}:`,
                error,
            );
            throw error;
        }
    };

    // Add display name for better debugging
    EntityCrudPage.displayName = `${config.entityName}CrudPage`;

    return EntityCrudPage;
}
