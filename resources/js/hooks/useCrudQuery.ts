'use client';

import { useOptionalTranslation } from '@/contexts/i18n-context';
import axios from '@/lib/axios';
import { handleApiError } from '@/utils/errorHandling';
import { translateOr } from '@/utils/i18nFallback';
import { useQuery, UseQueryResult } from '@tanstack/react-query';

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
    summary?: Record<string, unknown>;
}

/**
 * Normalisasi meta pagination agar `from`/`to` selalu konsisten dengan jumlah
 * baris yang tampil. Beberapa endpoint report mengembalikan data tanpa `meta`
 * (atau `meta` tanpa `from`/`to`), yang membuat UI menampilkan
 * "Showing 0 to 0 of 0" padahal ada baris (W4-20).
 */
function normalizeMeta<T>(
    response: Partial<ApiResponse<T>> | undefined,
    perPage: number,
    page: number,
): ApiResponse<T>['meta'] {
    const rows = Array.isArray(response?.data) ? response.data : [];
    const meta = response?.meta;
    const total = meta?.total ?? rows.length;
    const from =
        meta?.from ??
        (rows.length === 0
            ? 0
            : (meta?.current_page ?? page - 1) * perPage + 1);
    const to = meta?.to ?? (rows.length === 0 ? 0 : from + rows.length - 1);

    return {
        current_page: meta?.current_page ?? page,
        per_page: meta?.per_page ?? perPage,
        total,
        last_page: meta?.last_page ?? Math.max(1, Math.ceil(total / perPage)),
        from,
        to,
    };
}

export interface UseCrudQueryOptions {
    endpoint: string;
    queryKey: string[];
    entityName: string;
    toastEntityName?: string;
    pagination: PaginationState;
    filters?: FilterState;
    enabled?: boolean;
}

export interface UseCrudQueryReturn<Entity> extends Omit<
    UseQueryResult<ApiResponse<Entity>, Error>,
    'data'
> {
    data: Entity[];
    meta: ApiResponse<Entity>['meta'];
    summary?: ApiResponse<Entity>['summary'];
}

export function useCrudQuery<Entity>({
    endpoint,
    queryKey,
    entityName,
    toastEntityName,
    pagination,
    filters = {},
    enabled = true,
}: UseCrudQueryOptions): UseCrudQueryReturn<Entity> {
    const { t } = useOptionalTranslation();
    const displayName = toastEntityName ?? entityName;
    const query = useQuery<ApiResponse<Entity>, Error>({
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

                return response.data
                    ? {
                          ...response.data,
                          meta: normalizeMeta(
                              response.data,
                              pagination.per_page,
                              pagination.page,
                          ),
                      }
                    : {
                          data: [],
                          meta: normalizeMeta(
                              undefined,
                              pagination.per_page,
                              pagination.page,
                          ),
                      };
            } catch (error) {
                handleApiError(
                    error,
                    translateOr(
                        t,
                        'crud_toast.load_failed',
                        `Failed to load ${entityName}`,
                        { entity: displayName },
                    ),
                );
                return {
                    data: [],
                    meta: normalizeMeta(
                        undefined,
                        pagination.per_page,
                        pagination.page,
                    ),
                };
            }
        },
        enabled,
    });

    return {
        ...query,
        data: query.data?.data || [],
        meta:
            query.data?.meta ||
            normalizeMeta(undefined, pagination.per_page, pagination.page),
        summary: query.data?.summary,
    };
}
