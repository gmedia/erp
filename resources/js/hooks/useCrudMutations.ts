'use client';

import { useOptionalTranslation } from '@/contexts/i18n-context';
import axios from '@/lib/axios';
import { handleApiError, type ApiError } from '@/utils/errorHandling';
import { translateOr } from '@/utils/i18nFallback';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

export interface UseCrudMutationsOptions {
    endpoint: string;
    queryKey: string[];
    entityName: string;
    identifierKey?: string;
    onSuccess?: () => void;
    onError?: (error: ApiError) => void;
}

export interface UseCrudMutationsResult<Entity, FormData> {
    createMutation: ReturnType<typeof useMutation<Entity, Error, FormData>>;
    updateMutation: ReturnType<
        typeof useMutation<
            Entity,
            Error,
            { id: string | number; data: FormData }
        >
    >;
    deleteMutation: ReturnType<
        typeof useMutation<void, Error, string | number>
    >;
}

export function useCrudMutations<Entity, FormData>({
    endpoint,
    queryKey,
    entityName,
    onSuccess,
    onError,
}: UseCrudMutationsOptions): UseCrudMutationsResult<Entity, FormData> {
    const queryClient = useQueryClient();
    const { t } = useOptionalTranslation();
    const entity = { entity: entityName };
    const tToast = (key: string, fallback: string) =>
        translateOr(t, `crud_toast.${key}`, fallback, entity);

    const createMutation = useMutation<Entity, Error, FormData>({
        mutationFn: async (data: FormData) => {
            const response = await axios.post(endpoint, data);
            return response.data;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey });
            toast.success(
                tToast('created', `${entityName} created successfully`),
            );
            onSuccess?.();
        },
        onError: (error) => {
            const parsedError = handleApiError(
                error,
                tToast('create_failed', `Failed to create ${entityName}`),
            );
            onError?.(parsedError);
        },
    });

    const updateMutation = useMutation<
        Entity,
        Error,
        { id: string | number; data: FormData }
    >({
        mutationFn: async ({ id, data }) => {
            if (data instanceof FormData) {
                // Multipart PUT is not parsed by PHP; Laravel needs method spoofing.
                data.append('_method', 'PUT');
                const response = await axios.post(`${endpoint}/${id}`, data);
                return response.data;
            }

            const response = await axios.put(`${endpoint}/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey });
            toast.success(
                tToast('updated', `${entityName} updated successfully`),
            );
            onSuccess?.();
        },
        onError: (error) => {
            const parsedError = handleApiError(
                error,
                tToast('update_failed', `Failed to update ${entityName}`),
            );
            onError?.(parsedError);
        },
    });

    const deleteMutation = useMutation<void, Error, string | number>({
        mutationFn: async (id) => {
            await axios.delete(`${endpoint}/${id}`);
        },
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey });
            toast.success(
                tToast('deleted', `${entityName} deleted successfully`),
            );
            onSuccess?.();
        },
        onError: (error) => {
            const parsedError = handleApiError(
                error,
                tToast('delete_failed', `Failed to delete ${entityName}`),
            );
            onError?.(parsedError);
        },
    });

    return {
        createMutation,
        updateMutation,
        deleteMutation,
    };
}
