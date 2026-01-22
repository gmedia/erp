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
    T extends Record<string, unknown>,
    FormData = unknown,
> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item?: T | null;
    onSubmit: (data: FormData) => void;
    isLoading: boolean;
}

export interface CrudPageConfig<
    T extends Record<string, unknown>,
    FormData,
    FilterType extends FilterState = FilterState,
> extends BaseCrudPageConfig<T, FilterType> {
    // UI configuration
    entityNamePlural: string;
    breadcrumbs: BreadcrumbItem[];

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    DataTableComponent: React.ComponentType<any>;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    FormComponent: React.ComponentType<any>;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ViewModalComponent?: React.ComponentType<any>;

    mapDataTableProps: (props: {
        data: T[];
        onAdd: () => void;
        onEdit: (item: T) => void;
        onDelete: (item: T) => void;
        onView: (item: T) => void;
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
    }) => Record<string, unknown>;

    mapViewModalProps?: (props: {
        open: boolean;
        onClose: () => void;
        item: T | null;
    }) => Record<string, unknown>;

    mapFormProps: (props: {
        open: boolean;
        onOpenChange: (open: boolean) => void;
        item?: T | null;
        onSubmit: (data: FormData) => void;
        isLoading: boolean;
    }) => Record<string, unknown>;
}

interface CrudPageProps<
    T extends Record<string, unknown>,
    FormData,
    FilterType extends FilterState = FilterState,
> {
    config: CrudPageConfig<T, FormData, FilterType>;
}

export function CrudPage<
    T extends Record<string, unknown>,
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
                onView: crudState.handleView,
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
            crudState.handleView,
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

    // Prepare view modal props
    const viewModalProps = useMemo(
        () =>
            config.mapViewModalProps
                ? config.mapViewModalProps({
                      open: crudState.isViewModalOpen,
                      onClose: crudState.handleViewClose,
                      item: crudState.viewItem,
                  })
                : {
                      open: crudState.isViewModalOpen,
                      onClose: crudState.handleViewClose,
                      item: crudState.viewItem,
                  },
        [
            config,
            crudState.isViewModalOpen,
            crudState.handleViewClose,
            crudState.viewItem,
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

                {/* Modals are rendered inside AppLayout to be within I18nProvider scope */}
                <config.FormComponent {...formProps} />

                <DeleteConfirmationDialog
                    open={!!crudState.itemToDelete}
                    onOpenChange={(open) =>
                        !open && crudState.handleDeleteCancel()
                    }
                    item={crudState.itemToDelete}
                    onConfirm={crudState.handleDeleteConfirm}
                    isLoading={crudState.isDeleting}
                    getDeleteMessage={crudState.getDeleteMessage}
                />

                {config.ViewModalComponent && (
                    <config.ViewModalComponent {...viewModalProps} />
                )}
            </AppLayout>
        </>
    );
}

// Memoize the component to prevent unnecessary re-renders
export default memo(CrudPage) as typeof CrudPage;
