'use client';

import { useState, useCallback } from 'react';

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
  setPagination: (newPagination: Partial<{ page: number; per_page: number }>) => void;
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
  const [filters, setFiltersState] = useState<T>(initialFilters);
  const [pagination, setPaginationState] = useState(initialPagination);

  const setFilters = useCallback((newFilters: Partial<T>) => {
    setFiltersState((prev) => ({
      ...prev,
      ...newFilters,
    }));

    if (resetPageOnFilterChange) {
      setPaginationState((prev) => ({ ...prev, page: 1 }));
    }
  }, [resetPageOnFilterChange]);

  const setPagination = useCallback((newPagination: Partial<{ page: number; per_page: number }>) => {
    setPaginationState((prev) => ({
      ...prev,
      ...newPagination,
    }));
  }, []);

  const resetFilters = useCallback(() => {
    setFiltersState(initialFilters);
    if (resetPageOnFilterChange) {
      setPaginationState((prev) => ({ ...prev, page: 1 }));
    }
  }, [initialFilters, resetPageOnFilterChange]);

  const resetPagination = useCallback(() => {
    setPaginationState(initialPagination);
  }, [initialPagination]);

  const handleFilterChange = useCallback((newFilters: Partial<T>) => {
    setFilters(newFilters);
  }, [setFilters]);

  const handleSearchChange = useCallback((search: string) => {
    setFilters({ ...filters, search } as Partial<T>);
  }, [filters, setFilters]);

  const handlePageChange = useCallback((page: number) => {
    setPagination({ page });
  }, [setPagination]);

  const handlePageSizeChange = useCallback((per_page: number) => {
    setPagination({ page: 1, per_page });
  }, [setPagination]);

  return {
    filters,
    pagination,
    setFilters,
    setPagination,
    resetFilters,
    resetPagination,
    handleFilterChange,
    handleSearchChange,
    handlePageChange,
    handlePageSizeChange,
  };
}
