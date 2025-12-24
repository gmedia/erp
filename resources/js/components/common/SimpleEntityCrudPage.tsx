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

// Unified factory function that creates appropriate CRUD pages based on entity configuration
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

// Simple entity CRUD page factory - refactored to reduce duplication
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
                        open: props.open,
                        onOpenChange: props.onOpenChange,
                        entity: props.item ? { name: props.item.name } : null,
                        onSubmit: props.onSubmit,
                        isLoading: props.isLoading,
                        entityName: config.entityName,
                    }),

                    getDeleteMessage: (item) => config.getDeleteMessage(item),
                }}
            />
        );
    };
}

// Complex entity CRUD page factory - simplified and consistent
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
