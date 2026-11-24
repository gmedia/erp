'use client';

import { memo, useMemo } from 'react';
import { Helmet } from 'react-helmet-async';

import { DataTableProps as BaseDataTableProps } from '@/components/common/DataTableCore';
import { DeleteConfirmationDialog } from '@/components/common/DeleteConfirmationDialog';
import { PageHeader } from '@/components/common/PageHeader';
import { type FilterState } from '@/hooks/useCrudFilters';
import {
    useCrudPage,
    type CrudPageConfig as BaseCrudPageConfig,
} from '@/hooks/useCrudPage';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

// Extend the base DataTable props with additional required props for CRUD operations
export interface DataTableProps<
    T,
    FilterType extends FilterState = FilterState,
> extends Omit<BaseDataTableProps<T>, 'onFilterChange' | 'filters'> {
    filters: FilterType;
    onFilterChange: (filters: Partial<FilterType>) => void;
}

export interface FormProps<T extends object, FormData = unknown> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item?: T | null;
    onSubmit: (data: FormData) => void;
    isLoading: boolean;
}

export interface CrudPageActionAccess {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
}

export interface CrudPageConfig<
    T extends object,
    FormData,
    FilterType extends FilterState = FilterState,
> extends BaseCrudPageConfig<T, FilterType> {
    // UI configuration
    entityNamePlural: string;
    /** Izin tombol aksi; undefined = semua tampil (perilaku lama). */
    actionAccess?: CrudPageActionAccess;
    /** Judul halaman memakai entityNamePlural; deskripsi opsional di bawahnya. */
    description?: string;
    breadcrumbs: BreadcrumbItem[];
    identifierKey?: string;
    loadDetailOnEdit?: boolean;
    detailLoadErrorMessage?: string;

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    DataTableComponent: React.ComponentType<any>;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    FormComponent: React.ComponentType<any>;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    ViewModalComponent?: React.ComponentType<any>;

    mapDataTableProps: (props: {
        data: T[];
        onAdd: (() => void) | undefined;
        onEdit: ((item: T) => void) | undefined;
        onDelete: ((item: T) => void) | undefined;
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
    T extends object,
    FormData,
    FilterType extends FilterState = FilterState,
> {
    config: CrudPageConfig<T, FormData, FilterType>;
}

export function CrudPage<
    T extends object,
    FormData,
    FilterType extends FilterState = FilterState,
>({ config }: Readonly<CrudPageProps<T, FormData, FilterType>>) {
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
        identifierKey: config.identifierKey,
        initialPagination: config.initialPagination,
        getDeleteMessage: config.getDeleteMessage,
        loadDetailOnEdit: config.loadDetailOnEdit,
        detailLoadErrorMessage: config.detailLoadErrorMessage,
    });

    const access = config.actionAccess;

    // Prepare data table props
    const dataTableProps = useMemo(
        () =>
            config.mapDataTableProps({
                data: crudState.data,
                onAdd:
                    access?.canCreate === false
                        ? undefined
                        : crudState.handleAdd,
                onEdit:
                    access?.canEdit === false
                        ? undefined
                        : crudState.handleEdit,
                onDelete:
                    access?.canDelete === false
                        ? undefined
                        : crudState.handleDelete,
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
            access,
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
            <Helmet>
                <title>{config.entityNamePlural}</title>
            </Helmet>

            <AppLayout breadcrumbs={config.breadcrumbs}>
                <div className="flex w-full flex-col gap-3 overflow-x-auto p-4">
                    <PageHeader
                        title={config.entityNamePlural}
                        description={config.description}
                    />
                    <div>
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
