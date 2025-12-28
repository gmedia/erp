'use client';

import React from 'react';
import { CrudPage } from '@/components/common/CrudPage';
import { DataTable } from '@/components/common/DataTableCore';
import { EntityConfig } from '@/utils/entityConfigs';

// Define form component types for better type safety
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

export interface ComplexFormProps<T extends { id: number; name: string }> extends BaseFormProps {
    [key: string]: unknown; // Dynamic prop name based on entity
    employee?: T | null;
}

export type FormComponentType = 'simple' | 'complex';

// Type guard to check if a form component accepts simple props
export function isSimpleFormComponent(
    component: React.ComponentType<any>
): component is React.ComponentType<SimpleFormProps> {
    return component.name === 'SimpleEntityForm' || component.displayName === 'SimpleEntityForm';
}

// Utility function to create form props mapper based on form type
export function createFormPropsMapper<T extends { id: number; name: string }>(config: EntityCrudConfig<T>) {
    return (crudProps: {
        open: boolean;
        onOpenChange: (open: boolean) => void;
        item?: T | null;
        onSubmit: (data: unknown) => void;
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
            } as SimpleFormProps;
        } else {
            return {
                ...baseProps,
                [config.entityName.toLowerCase()]: crudProps.item,
            } as ComplexFormProps<T>;
        }
    };
}

// Extended config with form type information
export interface EntityCrudConfig<
    T extends { id: number; name: string } = { id: number; name: string },
    FormData = unknown
> extends EntityConfig<T, FormData> {
    formType: FormComponentType;
    formComponent: React.ComponentType<SimpleFormProps | ComplexFormProps<T>>;
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
export function createEntityCrudPage(config: EntityCrudConfig): () => React.JSX.Element {
    // Validate input configuration
    if (!config) {
        throw new Error('Entity configuration is required');
    }

    if (!config.entityName || !config.apiEndpoint) {
        throw new Error('Entity configuration must include entityName and apiEndpoint');
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
            console.error(`Error rendering CRUD page for ${config.entityName}:`, error);
            throw error;
        }
    };

    // Add display name for better debugging
    EntityCrudPage.displayName = `${config.entityName}CrudPage`;

    return EntityCrudPage;
}
