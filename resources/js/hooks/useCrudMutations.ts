'use client';

import { handleApiError, type ApiError } from '@/utils/errorHandling';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
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
        typeof useMutation<Entity, Error, { id: string | number; data: FormData }>
    >;
    deleteMutation: ReturnType<typeof useMutation<void, Error, string | number>>;
}

export function useCrudMutations<Entity, FormData>({
    endpoint,
    queryKey,
    entityName,
    identifierKey = 'id',
    onSuccess,
    onError,
}: UseCrudMutationsOptions): UseCrudMutationsResult<Entity, FormData> {
    const queryClient = useQueryClient();

    const createMutation = useMutation<Entity, Error, FormData>({
        mutationFn: async (data: FormData) => {
            const response = await axios.post(endpoint, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey });
            toast.success(`${entityName} created successfully`);
            onSuccess?.();
        },
        onError: (error) => {
            const parsedError = handleApiError(
                error,
                `Failed to create ${entityName}`,
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
            const response = await axios.put(`${endpoint}/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey });
            toast.success(`${entityName} updated successfully`);
            onSuccess?.();
        },
        onError: (error) => {
            const parsedError = handleApiError(
                error,
                `Failed to update ${entityName}`,
            );
            onError?.(parsedError);
        },
    });

    const deleteMutation = useMutation<void, Error, string | number>({
        mutationFn: async (id) => {
            await axios.delete(`${endpoint}/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey });
            toast.success(`${entityName} deleted successfully`);
            onSuccess?.();
        },
        onError: (error) => {
            const parsedError = handleApiError(
                error,
                `Failed to delete ${entityName}`,
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
