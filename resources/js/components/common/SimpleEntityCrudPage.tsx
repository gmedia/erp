'use client';

import { CrudPage } from '@/components/common/CrudPage';
import { GenericDataTable } from '@/components/common/GenericDataTable';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { createSimpleEntityColumns } from '@/utils/columns';
import { type BreadcrumbItem } from '@/types';
import { type FilterState } from '@/hooks/useCrudFilters';
import { EntityConfig, SimpleEntityConfig, ComplexEntityConfig } from '@/utils/entityConfigs';

// Static imports for complex entities
import { EmployeeForm } from '@/components/employees/EmployeeForm';
import { employeeColumns } from '@/components/employees/EmployeeColumns';

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

        if (config.type === 'complex') {
            const complexConfig = config as ComplexEntityConfig<T, FormData, FilterType>;

            // For now, only handle Employee as complex entity
            if (config.entityName === 'Employee') {
                return (
                    <CrudPage<T, FormData, FilterType>
                        config={{
                            entityName: complexConfig.entityName,
                            entityNamePlural: complexConfig.entityNamePlural,
                            apiEndpoint: complexConfig.apiEndpoint,
                            queryKey: complexConfig.queryKey,
                            breadcrumbs: complexConfig.breadcrumbs,

                            DataTableComponent: GenericDataTable,
                            FormComponent: EmployeeForm,

                            initialFilters: complexConfig.initialFilters,

                            mapDataTableProps: (props) => ({
                                data: props.data,
                                onAdd: props.onAdd,
                                onEdit: props.onEdit,
                                onDelete: props.onDelete,
                                pagination: props.pagination,
                                onPageChange: props.onPageChange,
                                onPageSizeChange: props.onPageSizeChange,
                                onSearchChange: props.onSearchChange,
                                isLoading: props.isLoading,
                                filterValue: props.filterValue,
                                filters: props.filters,
                                onFilterChange: props.onFilterChange,
                                onResetFilters: props.onResetFilters,
                                columns: employeeColumns,
                                exportEndpoint: complexConfig.exportEndpoint,
                                filterFields: complexConfig.filterFields,
                            }),

                            mapFormProps: (props) => ({
                                open: props.open,
                                onOpenChange: props.onOpenChange,
                                employee: props.item, // For EmployeeForm compatibility
                                onSubmit: props.onSubmit,
                                isLoading: props.isLoading,
                            }),

                            getDeleteMessage: complexConfig.getDeleteMessage,
                        }}
                    />
                );
            }

            throw new Error(`Complex entity '${config.entityName}' not supported yet`);
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
