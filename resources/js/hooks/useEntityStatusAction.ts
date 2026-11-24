'use client';

import axiosInstance from '@/lib/axios';
import { useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';

interface UseEntityStatusActionOptions<T, A extends string> {
    endpoint: string;
    queryKey: string;
    successMessages: Record<A, string>;
    failedMessage: string;
    onSuccess?: (item: T) => void;
}

function getErrorMessage(error: unknown, fallback: string): string {
    if (!axios.isAxiosError(error)) return fallback;

    const data = error.response?.data as
        | { message?: string; errors?: Record<string, string[]> }
        | undefined;

    return data?.errors?.status?.[0] || data?.message || fallback;
}

/** Aksi status per dokumen: POST {endpoint}/{id}/{action}, toast, lalu segarkan daftar. */
export function useEntityStatusAction<
    T extends { id: number | string },
    A extends string,
>({
    endpoint,
    queryKey,
    successMessages,
    failedMessage,
    onSuccess,
}: UseEntityStatusActionOptions<T, A>) {
    const queryClient = useQueryClient();
    const [processing, setProcessing] = useState(false);

    const run = async (item: T, action: A): Promise<boolean> => {
        setProcessing(true);
        try {
            const response = await axiosInstance.post<{ data: T }>(
                `${endpoint}/${item.id}/${action}`,
            );

            toast.success(successMessages[action]);
            void queryClient.invalidateQueries({ queryKey: [queryKey] });
            onSuccess?.(response.data.data);
            return true;
        } catch (error: unknown) {
            toast.error(getErrorMessage(error, failedMessage));
            return false;
        } finally {
            setProcessing(false);
        }
    };

    return { run, processing };
}
