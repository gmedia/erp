'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { createSimpleEntityColumns } from '@/utils/columns';
import { type BreadcrumbItem } from '@/types';
import { EntityConfig, SimpleEntityConfig } from '@/utils/entityConfigs';

// Unified CRUD page factory that handles both simple and complex entities
export function createEntityCrudPage<
    T extends { id: number; name: string; created_at: string; updated_at: string },
    FormData,
    FilterType extends Record<string, any> = Record<string, any>
>(config: EntityConfig<T, FormData, FilterType>) {
    return function EntityCrudPageComponent() {
        // For now, handle simple entities only. Complex entities will be handled separately
        // to avoid complex dynamic imports and type issues
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

                        initialFilters: { search: '' } as unknown as FilterType,

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

        // For complex entities, throw an error for now - they should use the original CrudPage directly
        throw new Error(`Complex entity type '${(config as any).type}' not supported by createEntityCrudPage. Use CrudPage directly.`);
    };
}

// Legacy alias for backward compatibility
export const createSimpleEntityCrudPage = createEntityCrudPage;

// Type for creating entity configurations
export interface SimpleEntityCrudPageConfig<T, FormData, FilterType extends Record<string, any> = Record<string, any>> {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    exportEndpoint: string;
    filterPlaceholder: string;
    getDeleteMessage?: (item: T) => string;
}
