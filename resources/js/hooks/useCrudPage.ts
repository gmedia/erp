'use client';

import { useCrudFilters, type FilterState } from '@/hooks/useCrudFilters';
import { useCrudMutations } from '@/hooks/useCrudMutations';
import { useCrudQuery } from '@/hooks/useCrudQuery';
import { type ApiError } from '@/utils/errorHandling';
import { useCallback, useMemo, useState } from 'react';

export interface CrudPageConfig<
    T extends { id: number; name: string },
    FilterType extends FilterState = FilterState,
> {
    // Basic configuration
    entityName: string;
    apiEndpoint: string;
    queryKey: string[];

    // Optional callbacks for customization
    onCreateSuccess?: () => void;
    onUpdateSuccess?: () => void;
    onDeleteSuccess?: () => void;
    onError?: (error: ApiError) => void;

    // Filter configuration
    initialFilters?: FilterType;
    initialPagination?: {
        page: number;
        per_page: number;
    };

    // Custom formatting for delete message
    getDeleteMessage?: (item: T) => string;
}

export interface CrudPageState<T, FormData, FilterType extends FilterState> {
    // FormData is used in the function signature but not in the interface
    // State
    isFormOpen: boolean;
    selectedItem: T | null;
    itemToDelete: T | null;

    // Data and loading states
    data: T[];
    isLoading: boolean;
    meta: {
        current_page: number;
        per_page: number;
        total: number;
        last_page: number;
        from?: number;
        to?: number;
    };

    // Filters and pagination
    filters: FilterType;
    pagination: { page: number; per_page: number };
    filterValue: string;

    // Mutation states
    isCreating: boolean;
    isUpdating: boolean;
    isDeleting: boolean;

    // Computed values
    tablePagination: {
        page: number;
        per_page: number;
        total: number;
        last_page: number;
        from: number;
        to: number;
    };
    getDeleteMessage: (item: T) => string;

    // Actions
    handleAdd: () => void;
    handleEdit: (item: T) => void;
    handleDelete: (item: T) => void;
    handleFormSubmit: (data: FormData) => void;
    handleDeleteConfirm: () => void;
    handleDeleteCancel: () => void;
    handleFormClose: (open: boolean) => void;
    handleFilterChange: (filters: Partial<FilterType>) => void;
    handleSearchChange: (search: string) => void;
    handlePageChange: (page: number) => void;
    handlePageSizeChange: (per_page: number) => void;
    resetFilters: () => void;
}

export function useCrudPage<
    T extends { id: number; name: string },
    FormData,
    FilterType extends FilterState = FilterState,
>(
    config: CrudPageConfig<T, FilterType>,
): CrudPageState<T, FormData, FilterType> {
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
        initialFilters:
            config.initialFilters || ({ search: '' } as unknown as FilterType),
        initialPagination: config.initialPagination || {
            page: 1,
            per_page: 15,
        },
    });

    // CRUD operations
    const { createMutation, updateMutation, deleteMutation } = useCrudMutations<
        T,
        FormData
    >({
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

    const handleFormSubmit = useCallback(
        (data: FormData) => {
            if (selectedItem) {
                updateMutation.mutate(
                    { id: selectedItem.id, data },
                    {
                        onSuccess: () => {
                            setIsFormOpen(false);
                            setSelectedItem(null);
                            config.onUpdateSuccess?.();
                        },
                    },
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
        },
        [selectedItem, updateMutation, createMutation, config],
    );

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

    const handleDeleteCancel = useCallback(() => {
        setItemToDelete(null);
    }, []);

    const handleFormClose = useCallback((open: boolean) => {
        setIsFormOpen(open);
        if (!open) {
            setSelectedItem(null);
        }
    }, []);

    // Memoize expensive computations
    const getDeleteMessage = useMemo(
        () =>
            config.getDeleteMessage ||
            ((item: T) => {
                const name =
                    item.name || `this ${config.entityName.toLowerCase()}`;
                return `This action cannot be undone. This will permanently delete ${name}'s ${config.entityName.toLowerCase()} record.`;
            }),
        [config.getDeleteMessage, config.entityName],
    );

    const tablePagination = useMemo(
        () => ({
            page: meta.current_page,
            per_page: meta.per_page,
            total: meta.total,
            last_page: meta.last_page,
            from: meta.from || 0,
            to: meta.to || 0,
        }),
        [meta],
    );

    const filterValue = useMemo(
        () => (filters as { search?: string }).search || '',
        [filters],
    );

    return {
        // State
        isFormOpen,
        selectedItem,
        itemToDelete,

        // Data and loading states
        data,
        isLoading,
        meta,

        // Filters and pagination
        filters,
        pagination,
        filterValue,

        // Mutation states
        isCreating: createMutation.isPending,
        isUpdating: updateMutation.isPending,
        isDeleting: deleteMutation.isPending,

        // Computed values
        tablePagination,
        getDeleteMessage,

        // Actions
        handleAdd,
        handleEdit,
        handleDelete,
        handleFormSubmit,
        handleDeleteConfirm,
        handleDeleteCancel,
        handleFormClose,
        handleFilterChange,
        handleSearchChange,
        handlePageChange,
        handlePageSizeChange,
        resetFilters,
    };
}
