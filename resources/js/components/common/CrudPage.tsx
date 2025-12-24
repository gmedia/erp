'use client';

import { Head } from '@inertiajs/react';
import { useState, useCallback } from 'react';

import AppLayout from '@/layouts/app-layout';
import { DeleteConfirmationDialog } from '@/components/common/DeleteConfirmationDialog';
import { DataTableProps as BaseDataTableProps } from '@/components/common/DataTableCore';
import { useCrudFilters, type FilterState } from '@/hooks/useCrudFilters';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { useCrudMutations } from '@/hooks/useCrudMutations';
import { type BreadcrumbItem } from '@/types';

// Extend the base DataTable props with additional required props for CRUD operations
export interface DataTableProps<T, FilterType extends FilterState = FilterState> extends Omit<BaseDataTableProps<T>, 'onFilterChange' | 'filters'> {
    filters: FilterType;
    onFilterChange: (filters: Partial<FilterType>) => void;
}

export interface FormProps<T, FormData> {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    item?: T | null;
    onSubmit: (data: FormData) => void;
    isLoading: boolean;
}

export interface CrudPageConfig<
    T extends { id: number; name: string },
    FormData,
    FilterType extends FilterState = FilterState
> {
    // Basic configuration
    entityName: string;
    entityNamePlural: string;
    apiEndpoint: string;
    queryKey: string[];
    breadcrumbs: BreadcrumbItem[];

    // Component configuration - using generic prop interfaces
    DataTableComponent: React.ComponentType<any>;
    FormComponent: React.ComponentType<any>;

    // Optional callbacks for customization
    onCreateSuccess?: () => void;
    onUpdateSuccess?: () => void;
    onDeleteSuccess?: () => void;
    onError?: (error: Error) => void;

    // Filter configuration
    initialFilters?: FilterType;
    initialPagination?: {
        page: number;
        per_page: number;
    };

    // Custom formatting for delete message
    getDeleteMessage?: (item: T) => string;

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
    FilterType extends FilterState = FilterState
> {
    config: CrudPageConfig<T, FormData, FilterType>;
}

export function CrudPage<
    T extends { id: number; name: string },
    FormData,
    FilterType extends FilterState = FilterState
>({
    config,
}: CrudPageProps<T, FormData, FilterType>) {
    // State management
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedItem, setSelectedItem] = useState<T | null>(null);
    const [itemToDelete, setItemToDelete] = useState<T | null>(null);

    // Filter and pagination management
    const {
        filters,
        pagination,
        setPagination,
        handleFilterChange,
        handleSearchChange,
        handlePageChange,
        handlePageSizeChange,
        resetFilters,
    } = useCrudFilters<FilterType>({
        initialFilters: config.initialFilters || ({ search: '' } as unknown as FilterType),
        initialPagination: config.initialPagination || { page: 1, per_page: 15 },
    });

    // CRUD operations
    const {
        createMutation,
        updateMutation,
        deleteMutation,
    } = useCrudMutations<T, FormData>({
        endpoint: config.apiEndpoint,
        queryKey: config.queryKey,
        entityName: config.entityName,
        onSuccess: () => {
            // Reset pagination to first page after create/update/delete
            setPagination({ page: 1, per_page: pagination.per_page });
        },
        onError: config.onError,
    });

    // Data fetching
    const { data, isLoading, meta } = useCrudQuery<T>({
        endpoint: config.apiEndpoint,
        queryKey: config.queryKey,
        entityName: config.entityName,
        pagination,
        filters,
    });

    // Event handlers
    const handleAdd = useCallback(() => {
        setSelectedItem(null);
        setIsFormOpen(true);
    }, []);

    const handleEdit = useCallback((item: T) => {
        setSelectedItem(item);
        setIsFormOpen(true);
    }, []);

    const handleDelete = useCallback((item: T) => {
        setItemToDelete(item);
    }, []);

    const handleFormSubmit = useCallback((data: FormData) => {
        if (selectedItem) {
            updateMutation.mutate(
                { id: selectedItem.id, data },
                {
                    onSuccess: () => {
                        setIsFormOpen(false);
                        setSelectedItem(null);
                        config.onUpdateSuccess?.();
                    },
                }
            );
        } else {
            createMutation.mutate(data, {
                onSuccess: () => {
                    setIsFormOpen(false);
                    setSelectedItem(null);
                    config.onCreateSuccess?.();
                },
            });
        }
    }, [selectedItem, updateMutation, createMutation, config]);

    const handleDeleteConfirm = useCallback(() => {
        if (itemToDelete) {
            deleteMutation.mutate(itemToDelete.id, {
                onSuccess: () => {
                    setItemToDelete(null);
                    config.onDeleteSuccess?.();
                },
            });
        }
    }, [itemToDelete, deleteMutation, config]);

    const handleFormClose = useCallback((open: boolean) => {
        setIsFormOpen(open);
        if (!open) {
            setSelectedItem(null);
        }
    }, []);

    // Default delete message
    const getDeleteMessage = config.getDeleteMessage || ((item: T) => {
        const name = (item as any).name || `this ${config.entityName.toLowerCase()}`;
        return `This action cannot be undone. This will permanently delete ${name}'s ${config.entityName.toLowerCase()} record.`;
    });

    // Prepare data table props
    const dataTableProps = config.mapDataTableProps({
        data,
        onAdd: handleAdd,
        onEdit: handleEdit,
        onDelete: handleDelete,
        pagination: {
            page: meta.current_page,
            per_page: meta.per_page,
            total: meta.total,
            last_page: meta.last_page,
            from: meta.from || 0,
            to: meta.to || 0,
        },
        onPageChange: handlePageChange,
        onPageSizeChange: handlePageSizeChange,
        onSearchChange: handleSearchChange,
        isLoading,
        filterValue: (filters as { search?: string }).search || '',
        filters,
        onFilterChange: handleFilterChange,
        onResetFilters: resetFilters,
    });

    // Prepare form props
    const formProps = config.mapFormProps({
        open: isFormOpen,
        onOpenChange: handleFormClose,
        item: selectedItem,
        onSubmit: handleFormSubmit,
        isLoading: createMutation.isPending || updateMutation.isPending,
    });

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
                open={!!itemToDelete}
                onOpenChange={(open) => !open && setItemToDelete(null)}
                item={itemToDelete}
                onConfirm={handleDeleteConfirm}
                isLoading={deleteMutation.isPending}
                getDeleteMessage={getDeleteMessage}
            />
        </>
    );
}
