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

// Simple entity CRUD page factory
function createSimpleEntityCrudPage(config: SimpleEntityConfig) {
    return function SimpleEntityPage() {
        return (
            <CrudPage
                config={{
                    entityName: config.entityName,
                    entityNamePlural: config.entityNamePlural,
                    apiEndpoint: config.apiEndpoint,
                    queryKey: config.queryKey,
                    breadcrumbs: config.breadcrumbs,
                    initialFilters: { search: '' },

                    DataTableComponent: DataTable,
                    FormComponent: SimpleEntityForm,

                    mapDataTableProps: (props) => ({
                        ...props,
                        columns: createSimpleEntityColumns(),
                        exportEndpoint: config.exportEndpoint,
                        filterFields: createSimpleEntityFilterFields(config.filterPlaceholder),
                        entityName: config.entityName,
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

// Complex entity CRUD page factory
function createComplexEntityCrudPage(config: ComplexEntityConfig) {
    return function ComplexEntityPage() {
        return (
            <CrudPage
                config={{
                    entityName: config.entityName,
                    entityNamePlural: config.entityNamePlural,
                    apiEndpoint: config.apiEndpoint,
                    queryKey: config.queryKey,
                    breadcrumbs: config.breadcrumbs,
                    initialFilters: config.initialFilters || { search: '' },

                    DataTableComponent: DataTable,
                    FormComponent: EmployeeForm,

                    mapDataTableProps: (props) => ({
                        ...props,
                        columns: employeeColumns,
                        exportEndpoint: config.exportEndpoint,
                        filterFields: createEmployeeFilterFields(),
                        entityName: config.entityName,
                    }),

                    mapFormProps: (props) => ({
                        open: props.open,
                        onOpenChange: props.onOpenChange,
                        employee: props.item, // Employee form expects 'employee' prop
                        onSubmit: props.onSubmit,
                        isLoading: props.isLoading,
                    }),

                    getDeleteMessage: config.getDeleteMessage,
                }}
            />
        );
    };
}

// Unified factory function that delegates to the appropriate implementation
export function createEntityCrudPage(config: EntityConfig) {
    if (config.type === 'simple') {
        return createSimpleEntityCrudPage(config);
    }

    if (config.type === 'complex') {
        // Currently only employees are supported as complex entities
        if (config.entityName === 'Employee') {
            return createComplexEntityCrudPage(config);
        }
        throw new Error(`Unsupported complex entity: ${config.entityName}`);
    }

    throw new Error(`Unknown entity type: ${(config as any).type}`);
}
