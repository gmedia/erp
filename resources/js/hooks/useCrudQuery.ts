'use client';

import { useQuery, UseQueryResult } from '@tanstack/react-query';
import axios from 'axios';
import { toast } from 'sonner';

export interface PaginationState {
  page: number;
  per_page: number;
}

export interface FilterState {
  search?: string;
  [key: string]: string | number | undefined;
}

export interface ApiResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
  };
}

export interface UseCrudQueryOptions<T> {
  endpoint: string;
  queryKey: string[];
  entityName: string;
  pagination: PaginationState;
  filters?: FilterState;
  enabled?: boolean;
}

export function useCrudQuery<T>({
  endpoint,
  queryKey,
  entityName,
  pagination,
  filters = {},
  enabled = true,
}: UseCrudQueryOptions<T>): UseQueryResult<ApiResponse<T>, Error> & {
  data: T[];
  meta: ApiResponse<T>['meta'];
} {
  const query = useQuery<ApiResponse<T>, Error>({
    queryKey: [...queryKey, pagination, filters],
    queryFn: async () => {
      try {
        const response = await axios.get(endpoint, {
          params: {
            page: pagination.page,
            per_page: pagination.per_page,
            ...filters,
          },
        });
        
        return (
          response.data || {
            data: [],
            meta: {
              current_page: 1,
              per_page: pagination.per_page,
              total: 0,
              last_page: 1,
            },
          }
        );
      } catch (error) {
        toast.error(`Failed to load ${entityName}`);
        return {
          data: [],
          meta: {
            current_page: 1,
            per_page: pagination.per_page,
            total: 0,
            last_page: 1,
          },
        };
      }
    },
    enabled,
  });

  return {
    ...query,
    data: query.data?.data || [],
    meta: query.data?.meta || {
      current_page: 1,
      per_page: pagination.per_page,
      total: 0,
      last_page: 1,
    },
  };
}