'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { createSimpleEntityColumns } from '@/utils/columns';
import { type BreadcrumbItem } from '@/types';

interface SimpleEntityCrudPageConfig<T, FormData, FilterType extends Record<string, any> = Record<string, any>> {
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];
    exportEndpoint: string;
    filterPlaceholder: string;
    getDeleteMessage?: (item: T) => string;
}

export function createSimpleEntityCrudPage<T extends { id: number; name?: string }, FormData, FilterType extends Record<string, any> = Record<string, any>>(
    config: SimpleEntityCrudPageConfig<T, FormData, FilterType>
) {
    return function SimpleEntityCrudPageComponent() {
        return (
            <CrudPage<T, FormData, FilterType>
                config={{
                    entityName: config.entityName,
                    entityNamePlural: config.entityNamePlural,
                    apiEndpoint: config.apiEndpoint,
                    queryKey: config.queryKey,
                    breadcrumbs: config.breadcrumbs,

                    DataTableComponent: GenericDataTable,
                    FormComponent: SimpleEntityForm,

                    initialFilters: { search: '' } as unknown as FilterType,

                    mapDataTableProps: (props) => ({
                        ...props,
                        columns: createSimpleEntityColumns<T>(),
                        exportEndpoint: config.exportEndpoint,
                        filterFields: createSimpleEntityFilterFields(config.filterPlaceholder),
                    }),

                    mapFormProps: (props) => ({
                        ...props,
                        entity: props.item,
                        entityName: config.entityName,
                    }),

                    getDeleteMessage: config.getDeleteMessage,
                }}
            />
        );
    };
}
