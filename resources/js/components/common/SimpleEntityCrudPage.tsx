'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { createSimpleEntityColumns } from '@/utils/columns';
import { type FilterState } from '@/hooks/useCrudFilters';
import { EntityConfig, SimpleEntityConfig, ComplexEntityConfig } from '@/utils/entityConfigs';
import { ComponentType } from 'react';
import { ColumnDef } from '@tanstack/react-table';
import { type BreadcrumbItem } from '@/types';

// Registry for complex entity components to avoid static imports
interface ComplexEntityComponents<
    TEntity,
    TFormData,
    TFilterType extends FilterState
> {
    DataTableComponent?: ComponentType<any>;
    FormComponent: ComponentType<any>;
    columns: ColumnDef<TEntity>[];
    filterFields?: Array<{
        name: keyof TFilterType;
        label: string;
        component: React.ReactNode;
    }>;
}

// Component registry - can be extended for new complex entities
const complexEntityRegistry: Record<string, ComplexEntityComponents<any, any, any>> = {};

// Register complex entity components dynamically
export function registerComplexEntity<
    TEntity,
    TFormData,
    TFilterType extends FilterState
>(
    entityName: string,
    components: ComplexEntityComponents<TEntity, TFormData, TFilterType>
) {
    complexEntityRegistry[entityName] = components;
}

// Unified CRUD page factory that handles both simple and complex entities
export function createEntityCrudPage<
    T extends { id: number; name: string; created_at: string; updated_at: string },
    FormData,
    FilterType extends FilterState = FilterState
>(config: EntityConfig<T, FormData, FilterType>) {
    return function EntityCrudPageComponent() {
        if (config.type === 'simple') {
            const simpleConfig = config as SimpleEntityConfig;
            return (
                <CrudPage<T, FormData, FilterType>
                    config={{
                        entityName: simpleConfig.entityName,
                        entityNamePlural: simpleConfig.entityNamePlural,
                        apiEndpoint: simpleConfig.apiEndpoint,
                        queryKey: simpleConfig.queryKey,
                        breadcrumbs: simpleConfig.breadcrumbs,

                        DataTableComponent: GenericDataTable,
                        FormComponent: SimpleEntityForm,

                        initialFilters: { search: '' } as FilterType,

                        mapDataTableProps: (props) => ({
                            ...props,
                            columns: createSimpleEntityColumns<T>(),
                            exportEndpoint: simpleConfig.exportEndpoint,
                            filterFields: createSimpleEntityFilterFields(simpleConfig.filterPlaceholder),
                        }),

                        mapFormProps: (props) => ({
                            ...props,
                            entity: props.item,
                            entityName: simpleConfig.entityName,
                        }),

                        getDeleteMessage: simpleConfig.getDeleteMessage,
                    }}
                />
            );
        }

        if (config.type === 'complex') {
            const complexConfig = config as ComplexEntityConfig<T, FormData, FilterType>;
            const registeredComponents = complexEntityRegistry[complexConfig.entityName];

            if (!registeredComponents) {
                throw new Error(
                    `Complex entity '${complexConfig.entityName}' not registered. ` +
                    `Use registerComplexEntity() to register components for this entity.`
                );
            }

            return (
                <CrudPage<T, FormData, FilterType>
                    config={{
                        entityName: complexConfig.entityName,
                        entityNamePlural: complexConfig.entityNamePlural,
                        apiEndpoint: complexConfig.apiEndpoint,
                        queryKey: complexConfig.queryKey,
                        breadcrumbs: complexConfig.breadcrumbs,

                        DataTableComponent: registeredComponents.DataTableComponent || GenericDataTable,
                        FormComponent: registeredComponents.FormComponent,

                        initialFilters: complexConfig.initialFilters || ({ search: '' } as FilterType),

                        mapDataTableProps: (props) => ({
                            ...props,
                            columns: registeredComponents.columns,
                            exportEndpoint: complexConfig.exportEndpoint,
                            filterFields: registeredComponents.filterFields || complexConfig.filterFields,
                        }),

                        mapFormProps: (props) => ({
                            open: props.open,
                            onOpenChange: props.onOpenChange,
                            [complexConfig.entityName.toLowerCase()]: props.item, // Dynamic prop name for compatibility
                            onSubmit: props.onSubmit,
                            isLoading: props.isLoading,
                        }),

                        getDeleteMessage: complexConfig.getDeleteMessage,
                    }}
                />
            );
        }

        throw new Error(`Unknown entity type '${(config as { type?: string }).type ?? 'undefined'}'`);
    };
}

// Legacy alias for backward compatibility
export const createSimpleEntityCrudPage = createEntityCrudPage;

// Type for creating entity configurations (deprecated - use EntityConfig instead)
export interface SimpleEntityCrudPageConfig {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    exportEndpoint: string;
    filterPlaceholder: string;
    getDeleteMessage?: (item: { name?: string }) => string;
}
