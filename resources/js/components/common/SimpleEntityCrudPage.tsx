'use client';

import React from 'react';
import { CrudPage } from '@/components/common/CrudPage';
import { DataTable } from '@/components/common/DataTableCore';
import { SimpleEntityForm } from '@/components/common/EntityForm';
import { createSimpleEntityFilterFields } from '@/components/common/filters';
import { createSimpleEntityColumns } from '@/utils/columns';
import { type FilterState } from '@/hooks/useCrudFilters';
import { EntityConfig, SimpleEntityConfig, ComplexEntityConfig } from '@/utils/entityConfigs';
import { ComponentType } from 'react';
import { ColumnDef } from '@tanstack/react-table';
import { type BreadcrumbItem } from '@/types';

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

                        DataTableComponent: DataTable,
                        FormComponent: SimpleEntityForm,

                        initialFilters: { search: '' } as FilterType,

                        mapDataTableProps: (props) => ({
                            ...props,
                            columns: createSimpleEntityColumns<T>(),
                            exportEndpoint: simpleConfig.exportEndpoint,
                            filterFields: createSimpleEntityFilterFields(simpleConfig.filterPlaceholder),
                            entityName: simpleConfig.entityName,
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

            // For now, only support employees as complex entity
            if (complexConfig.entityName === 'Employee') {
                // Dynamic imports for employee components
                const EmployeeFormPromise = import('@/components/employees/EmployeeForm').then(module => module.EmployeeForm);
                const EmployeeColumnsPromise = import('@/components/employees/EmployeeColumns').then(module => module.employeeColumns);
                const EmployeeFiltersPromise = import('@/components/employees/EmployeeFilters').then(module => module.createEmployeeFilterFields);

                // Create a component that handles the async loading
                const ComplexEntityPage = () => {
                    const [EmployeeForm, setEmployeeForm] = React.useState<React.ComponentType<any> | null>(null);
                    const [employeeColumns, setEmployeeColumns] = React.useState<any[]>([]);
                    const [createEmployeeFilterFields, setCreateEmployeeFilterFields] = React.useState<(() => any[]) | null>(null);

                    React.useEffect(() => {
                        Promise.all([EmployeeFormPromise, EmployeeColumnsPromise, EmployeeFiltersPromise]).then(
                            ([EmployeeFormModule, EmployeeColumnsModule, EmployeeFiltersModule]) => {
                                setEmployeeForm(() => EmployeeFormModule);
                                setEmployeeColumns(EmployeeColumnsModule);
                                setCreateEmployeeFilterFields(() => EmployeeFiltersModule);
                            }
                        );
                    }, []);

                    if (!EmployeeForm || !createEmployeeFilterFields) {
                        return <div>Loading...</div>;
                    }

                    return (
                        <CrudPage<T, FormData, FilterType>
                            config={{
                                entityName: complexConfig.entityName,
                                entityNamePlural: complexConfig.entityNamePlural,
                                apiEndpoint: complexConfig.apiEndpoint,
                                queryKey: complexConfig.queryKey,
                                breadcrumbs: complexConfig.breadcrumbs,

                                DataTableComponent: DataTable,
                                FormComponent: EmployeeForm,

                                initialFilters: complexConfig.initialFilters || ({ search: '' } as FilterType),

                                mapDataTableProps: (props) => ({
                                    ...props,
                                    columns: employeeColumns,
                                    exportEndpoint: complexConfig.exportEndpoint,
                                    filterFields: createEmployeeFilterFields(),
                                    entityName: complexConfig.entityName,
                                }),

                                mapFormProps: (props) => ({
                                    open: props.open,
                                    onOpenChange: props.onOpenChange,
                                    employee: props.item, // Use specific prop name for employees
                                    onSubmit: props.onSubmit,
                                    isLoading: props.isLoading,
                                }),

                                getDeleteMessage: complexConfig.getDeleteMessage,
                            }}
                        />
                    );
                };

                return <ComplexEntityPage />;
            }

            throw new Error(`Unsupported complex entity: ${complexConfig.entityName}`);
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
