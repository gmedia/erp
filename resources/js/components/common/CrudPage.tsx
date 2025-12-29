'use client';

import { Head } from '@inertiajs/react';
import { memo, useMemo } from 'react';

import { DataTableProps as BaseDataTableProps } from '@/components/common/DataTableCore';
import { DeleteConfirmationDialog } from '@/components/common/DeleteConfirmationDialog';
import { type FilterState } from '@/hooks/useCrudFilters';
import {
    useCrudPage,
    type CrudPageConfig as BaseCrudPageConfig,
} from '@/hooks/useCrudPage';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

// Extend the base DataTable props with additional required props for CRUD operations
export interface DataTableProps<T, FilterType extends FilterState = FilterState>
    extends Omit<BaseDataTableProps<T>, 'onFilterChange' | 'filters'> {
    filters: FilterType;
    onFilterChange: (filters: Partial<FilterType>) => void;
}

export interface FormProps<
    T extends { id: number; name: string },
    FormData = unknown,
> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item?: T | null;
    onSubmit: (data: FormData) => void;
    isLoading: boolean;
}

export interface CrudPageConfig<
    T extends { id: number; name: string },
    FormData,
    FilterType extends FilterState = FilterState,
> extends BaseCrudPageConfig<T, FormData, FilterType> {
    // UI configuration
    entityNamePlural: string;
    breadcrumbs: BreadcrumbItem[];

    // Component configuration - using generic prop interfaces
    DataTableComponent: React.ComponentType<any>;
    FormComponent: React.ComponentType<any>;

    // Props mapping functions to adapt generic props to component-specific props
    mapDataTableProps: (props: {
        data: T[];
        onAdd: () => void;
        onEdit: (item: T) => void;
        onDelete: (item: T) => void;
        pagination: {
            page: number;
            per_page: number;
            total: number;
            last_page: number;
            from?: number;
            to?: number;
        };
        onPageChange: (page: number) => void;
        onPageSizeChange: (per_page: number) => void;
        onSearchChange: (search: string) => void;
        isLoading: boolean;
        filterValue: string;
        filters: FilterType;
        onFilterChange: (filters: Partial<FilterType>) => void;
        onResetFilters: () => void;
    }) => any;

    mapFormProps: (props: {
        open: boolean;
        onOpenChange: (open: boolean) => void;
        item?: T | null;
        onSubmit: (data: FormData) => void;
        isLoading: boolean;
    }) => any;
}

interface CrudPageProps<
    T extends { id: number; name: string },
    FormData,
    FilterType extends FilterState = FilterState,
> {
    config: CrudPageConfig<T, FormData, FilterType>;
}

export function CrudPage<
    T extends { id: number; name: string },
    FormData,
    FilterType extends FilterState = FilterState,
>({ config }: CrudPageProps<T, FormData, FilterType>) {
    // Use the custom hook to manage all CRUD state and logic
    const crudState = useCrudPage<T, FormData, FilterType>({
        entityName: config.entityName,
        apiEndpoint: config.apiEndpoint,
        queryKey: config.queryKey,
        onCreateSuccess: config.onCreateSuccess,
        onUpdateSuccess: config.onUpdateSuccess,
        onDeleteSuccess: config.onDeleteSuccess,
        onError: config.onError,
        initialFilters: config.initialFilters,
        initialPagination: config.initialPagination,
        getDeleteMessage: config.getDeleteMessage,
    });

    // Prepare data table props
    const dataTableProps = useMemo(
        () =>
            config.mapDataTableProps({
                data: crudState.data,
                onAdd: crudState.handleAdd,
                onEdit: crudState.handleEdit,
                onDelete: crudState.handleDelete,
                pagination: crudState.tablePagination,
                onPageChange: crudState.handlePageChange,
                onPageSizeChange: crudState.handlePageSizeChange,
                onSearchChange: crudState.handleSearchChange,
                isLoading: crudState.isLoading,
                filterValue: crudState.filterValue,
                filters: crudState.filters,
                onFilterChange: crudState.handleFilterChange,
                onResetFilters: crudState.resetFilters,
            }),
        [
            config,
            crudState.data,
            crudState.handleAdd,
            crudState.handleEdit,
            crudState.handleDelete,
            crudState.tablePagination,
            crudState.handlePageChange,
            crudState.handlePageSizeChange,
            crudState.handleSearchChange,
            crudState.isLoading,
            crudState.filterValue,
            crudState.filters,
            crudState.handleFilterChange,
            crudState.resetFilters,
        ],
    );

    // Prepare form props
    const formProps = useMemo(
        () =>
            config.mapFormProps({
                open: crudState.isFormOpen,
                onOpenChange: crudState.handleFormClose,
                item: crudState.selectedItem,
                onSubmit: crudState.handleFormSubmit,
                isLoading: crudState.isCreating || crudState.isUpdating,
            }),
        [
            config,
            crudState.isFormOpen,
            crudState.handleFormClose,
            crudState.selectedItem,
            crudState.handleFormSubmit,
            crudState.isCreating,
            crudState.isUpdating,
        ],
    );

    return (
        <>
            <Head title={config.entityNamePlural} />

            <AppLayout breadcrumbs={config.breadcrumbs}>
                <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    <div className="rounded-lg bg-white">
                        <config.DataTableComponent {...dataTableProps} />
                    </div>
                </div>
            </AppLayout>

            <config.FormComponent {...formProps} />

            <DeleteConfirmationDialog
                open={!!crudState.itemToDelete}
                onOpenChange={(open) => !open && crudState.handleDeleteCancel()}
                item={crudState.itemToDelete}
                onConfirm={crudState.handleDeleteConfirm}
                isLoading={crudState.isDeleting}
                getDeleteMessage={crudState.getDeleteMessage}
            />
        </>
    );
}

// Memoize the component to prevent unnecessary re-renders
export default memo(CrudPage) as typeof CrudPage;
