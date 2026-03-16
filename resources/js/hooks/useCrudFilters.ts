'use client';

import { useCallback, useState } from 'react';

export interface FilterState {
    search?: string;
    [key: string]: string | number | undefined;
}

export interface UseCrudFiltersOptions<T extends FilterState = FilterState> {
    initialFilters?: T;
    initialPagination?: {
        page: number;
        per_page: number;
    };
    resetPageOnFilterChange?: boolean;
}

export interface UseCrudFiltersResult<T extends FilterState = FilterState> {
    filters: T;
    pagination: {
        page: number;
        per_page: number;
    };
    setFilters: (newFilters: Partial<T>) => void;
    setPagination: (
        newPagination: Partial<{ page: number; per_page: number }>,
    ) => void;
    resetFilters: () => void;
    resetPagination: () => void;
    handleFilterChange: (newFilters: Partial<T>) => void;
    handleSearchChange: (search: string) => void;
    handlePageChange: (page: number) => void;
    handlePageSizeChange: (per_page: number) => void;
}

export function useCrudFilters<T extends FilterState = FilterState>({
    initialFilters = {} as T,
    initialPagination = { page: 1, per_page: 15 },
    resetPageOnFilterChange = true,
}: UseCrudFiltersOptions<T> = {}): UseCrudFiltersResult<T> {
    const [filters, setFilters] = useState<T>(() => {
        if (globalThis.window === undefined) return initialFilters;

        const params = new URLSearchParams(globalThis.window.location.search);
        const merged = { ...(initialFilters as Record<string, unknown>) };

        for (const key of Object.keys(initialFilters)) {
            const value = params.get(key);
            if (value !== null) merged[key] = value;
        }

        if (!('search' in merged)) {
            const search = params.get('search');
            if (search !== null) merged.search = search;
        }

        return merged as T;
    });

    const [pagination, setPagination] = useState(() => {
        if (globalThis.window === undefined) return initialPagination;

        const params = new URLSearchParams(globalThis.window.location.search);
        const pageParam = params.get('page');
        const perPageParam = params.get('per_page');

        const page = pageParam ? Number(pageParam) : initialPagination.page;
        const per_page = perPageParam
            ? Number(perPageParam)
            : initialPagination.per_page;

        return {
            page:
                Number.isFinite(page) && page > 0
                    ? page
                    : initialPagination.page,
            per_page:
                Number.isFinite(per_page) && per_page > 0
                    ? per_page
                    : initialPagination.per_page,
        };
    });

    const setFiltersWithReset = useCallback(
        (newFilters: Partial<T>) => {
            setFilters((prev) => ({
                ...prev,
                ...newFilters,
            }));

            if (resetPageOnFilterChange) {
                setPagination((prev) => ({ ...prev, page: 1 }));
            }
        },
        [resetPageOnFilterChange],
    );

    const setPaginationWithMerge = useCallback(
        (newPagination: Partial<{ page: number; per_page: number }>) => {
            setPagination((prev) => ({
                ...prev,
                ...newPagination,
            }));
        },
        [],
    );

    const resetFilters = useCallback(() => {
        setFilters(initialFilters);
        if (resetPageOnFilterChange) {
            setPagination((prev) => ({ ...prev, page: 1 }));
        }
    }, [initialFilters, resetPageOnFilterChange]);

    const resetPagination = useCallback(() => {
        setPagination(initialPagination);
    }, [initialPagination]);

    const handleFilterChange = useCallback(
        (newFilters: Partial<T>) => {
            setFiltersWithReset(newFilters);
        },
        [setFiltersWithReset],
    );

    const handleSearchChange = useCallback(
        (search: string) => {
            setFiltersWithReset({ ...filters, search } as Partial<T>);
        },
        [filters, setFiltersWithReset],
    );

    const handlePageChange = useCallback(
        (page: number) => {
            setPaginationWithMerge({ page });
        },
        [setPaginationWithMerge],
    );

    const handlePageSizeChange = useCallback(
        (per_page: number) => {
            setPaginationWithMerge({ page: 1, per_page });
        },
        [setPaginationWithMerge],
    );

    return {
        filters,
        pagination,
        setFilters: setFiltersWithReset,
        setPagination: setPaginationWithMerge,
        resetFilters,
        resetPagination,
        handleFilterChange,
        handleSearchChange,
        handlePageChange,
        handlePageSizeChange,
    };
}
