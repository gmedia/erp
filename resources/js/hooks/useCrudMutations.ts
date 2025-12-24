'use client';

import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { toast } from 'sonner';

export interface UseCrudMutationsOptions<Entity, FormData> {
  endpoint: string;
  queryKey: string[];
  entityName: string;
  onSuccess?: () => void;
  onError?: (error: Error) => void;
}

export interface UseCrudMutationsResult<Entity, FormData> {
  createMutation: ReturnType<typeof useMutation<Entity, Error, FormData>>;
  updateMutation: ReturnType<typeof useMutation<Entity, Error, { id: number; data: FormData }>>;
  deleteMutation: ReturnType<typeof useMutation<void, Error, number>>;
}

export function useCrudMutations<Entity, FormData>({
  endpoint,
  queryKey,
  entityName,
  onSuccess,
  onError,
}: UseCrudMutationsOptions<Entity, FormData>): UseCrudMutationsResult<Entity, FormData> {
  const queryClient = useQueryClient();

  const handleError = (error: Error & { response?: { data?: { message?: string } } }) => {
    const message = error?.response?.data?.message || `Failed to process ${entityName}`;
    toast.error(message);
    onError?.(error);
  };

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
    onError: handleError,
  });

  const updateMutation = useMutation<Entity, Error, { id: number; data: FormData }>({
    mutationFn: async ({ id, data }: { id: number; data: FormData }) => {
      const response = await axios.put(`${endpoint}/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey });
      toast.success(`${entityName} updated successfully`);
      onSuccess?.();
    },
    onError: handleError,
  });

  const deleteMutation = useMutation<void, Error, number>({
    mutationFn: async (id: number) => {
      await axios.delete(`${endpoint}/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey });
      toast.success(`${entityName} deleted successfully`);
      onSuccess?.();
    },
    onError: handleError,
  });

  return {
    createMutation,
    updateMutation,
    deleteMutation,
  };
}
